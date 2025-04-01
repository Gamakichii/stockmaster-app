import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score
import sys
import os # Added for os.path.exists

# --- Configuration ---
input_csv_path = 'phishing_data.csv'
output_csv_path = 'phishing_data_with_predictions.csv'

# Select features using the CORRECTED names based on your header list
feature_cols = [
    'length_url',
    'nb_dots', # CORRECTED from number_of_dots_in_url
    'ip',
    'nb_hyphens',
    'nb_slash'
]
# Use the CORRECT target column name from your header list
target_col = 'status' # CORRECTED from Type
prediction_col_name = 'predicted_status' # Name for the new column

print(f"--- Starting Prediction Workflow Example ---")
print(f"Reading input CSV: {input_csv_path}")

# --- Load Data ---
if not os.path.exists(input_csv_path):
     print(f"Error: Input CSV file not found at '{input_csv_path}'. Make sure it's in the same directory as the script.", file=sys.stderr)
     sys.exit(1)

try:
    df = pd.read_csv(input_csv_path)
    # Basic cleaning: convert headers to lowercase for consistent access
    df.columns = df.columns.str.strip().str.lower()
    print(f"Loaded {len(df)} rows. Columns found: {list(df.columns)}")

except FileNotFoundError: # This check might be redundant now but kept for safety
    print(f"Error: Input CSV file not found at '{input_csv_path}'.", file=sys.stderr)
    sys.exit(1)
except Exception as e:
    print(f"Error loading CSV: {e}", file=sys.stderr)
    sys.exit(1)


# --- Prepare Data ---
print(f"Preparing data using features: {feature_cols} and target: {target_col}")
try:
    # Check if all needed columns exist (using lowercase names now)
    required_cols_check = feature_cols + [target_col]
    missing_cols = [col for col in required_cols_check if col not in df.columns]
    if missing_cols:
        # Provide a more specific error message if the target column itself is missing after lowercasing
        if target_col not in df.columns:
             raise ValueError(f"Target column '{target_col}' not found after converting headers to lowercase. Check original CSV header spelling.")
        else:
             raise ValueError(f"Missing required feature columns after converting headers to lowercase: {missing_cols}. Check original CSV header spellings.")

    # Simple handling of missing data: drop rows with NaN in selected columns
    df_clean = df[required_cols_check].dropna() # Use the combined list
    rows_dropped = len(df) - len(df_clean)
    if rows_dropped > 0:
        print(f"Dropped {rows_dropped} rows due to missing values in selected columns.")

    if len(df_clean) == 0:
         raise ValueError("No valid data remaining after handling missing values.")

    X = df_clean[feature_cols]
    y = df_clean[target_col] # Target is now 'status'

except ValueError as ve:
    print(f"Data Preparation Error: {ve}", file=sys.stderr)
    sys.exit(1)
except Exception as e:
    print(f"An unexpected error occurred during data preparation: {e}", file=sys.stderr)
    sys.exit(1)


# --- Train/Test Split (Optional but good practice for seeing test accuracy) ---
print("Splitting data into training and testing sets (80/20)...")
try:
    # Check if target variable has more than 1 class for stratification
    if len(y.unique()) > 1:
        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)
    else:
        print("Warning: Target variable has only one class. Cannot stratify. Performing regular split.", file=sys.stderr)
        X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    print(f"Training set size: {len(X_train)}, Test set size: {len(X_test)}")
except ValueError as ve:
    print(f"Warning: Error during stratified split (maybe too few samples?): {ve}. Performing non-stratified split.", file=sys.stderr)
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)


# --- Train a Simple Model ---
print("Training simple Logistic Regression model...")
try:
    model = LogisticRegression(max_iter=1000, random_state=42) # Basic model
    model.fit(X_train, y_train)
    print("Model training complete.")
except Exception as e:
    print(f"Error during model training: {e}", file=sys.stderr)
    sys.exit(1)


# --- Evaluate on Test Set (Optional - just for info) ---
try:
    y_pred_test = model.predict(X_test)
    test_accuracy = accuracy_score(y_test, y_pred_test)
    print(f"Accuracy on test set (for this simple model): {test_accuracy:.4f}")
except Exception as e:
    print(f"Error during test set evaluation: {e}", file=sys.stderr)
    # Continue to predict on full set anyway

# --- Generate Predictions for the ENTIRE Cleaned Dataset ---
# We use the cleaned X data here
print("Generating predictions for the cleaned dataset...")
try:
    # Use X which corresponds to df_clean
    all_predictions = model.predict(X)
except Exception as e:
     print(f"Error generating predictions on full dataset: {e}", file=sys.stderr)
     sys.exit(1)

# --- Add Predictions to DataFrame ---
# Add predictions back to the DataFrame that had NaNs dropped (df_clean)
df_clean[prediction_col_name] = all_predictions
print(f"Added '{prediction_col_name}' column.")

# --- Save Results ---
print(f"Saving results with predictions to: {output_csv_path}")
try:
    # Save the df_clean which has predictions only for rows without NaNs in selected cols
    # This ensures the prediction column aligns correctly with the rows used
    df_clean.to_csv(output_csv_path, index=False)

    print(f"--- Workflow Complete ---")
    print(f"Output file '{output_csv_path}' created successfully.")
    print(f"You can now upload '{output_csv_path}' to your server's data directory.")
    print(f"Remember to update '$csvFilePath' in 'phishing_report.php' to use this new file.")


except Exception as e:
    print(f"Error saving output CSV: {e}", file=sys.stderr)
    sys.exit(1)
