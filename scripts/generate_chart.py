#!/usr/bin/env python3
import sys
import os
import pandas as pd
import matplotlib
matplotlib.use('Agg') # Use non-interactive backend for servers
import matplotlib.pyplot as plt
import numpy as np

# --- Chart Generation Function ---
def generate_chart(csv_path, output_image_path, chart_type, *args):
    """Generates a chart from CSV data or provided metrics and saves it."""
    # Define expected column names (lowercase) - used for checking and access
    ACTUAL_STATUS_COL = 'status' # Expecting 'legitimate' or 'phishing' strings now
    LEN_URL_COL = 'length_url'
    IP_COL = 'ip'
    DOTS_COL = 'nb_dots'
    # Add other headers from user's list if needed by future charts
    # HYPHENS_COL = 'nb_hyphens'
    # SLASH_COL = 'nb_slash'

    # --- Input Validation ---
    # (Keep input validation for paths as before)
    if chart_type != 'metrics_bar' and not os.path.exists(csv_path):
         print(f"Error: Input CSV not found at {csv_path}", file=sys.stderr); return False
    output_dir = os.path.dirname(output_image_path)
    if not os.path.isdir(output_dir) or not os.access(output_dir, os.W_OK):
         print(f"Error: Output directory problem: {output_dir}", file=sys.stderr); return False

    try:
        # --- Define Colors ---
        color_legit = 'mediumseagreen'
        color_phish = 'salmon'
        metric_colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728']

        # --- Load Data only if needed by chart type ---
        df = None
        if chart_type != 'metrics_bar':
            df = pd.read_csv(csv_path)
            df.columns = df.columns.str.strip().str.lower() # Normalize headers
             # ★★★ Convert status column to lowercase string for consistent comparison ★★★
            if ACTUAL_STATUS_COL in df.columns:
                 df[ACTUAL_STATUS_COL] = df[ACTUAL_STATUS_COL].astype(str).str.lower().str.strip()
            else:
                 # If status column is absolutely required for non-metric chart
                 if chart_type in ['status_pie', 'url_len_hist', 'len_vs_dots_scatter', 'avg_url_len_bar', 'ip_usage_bar']:
                     raise KeyError(f"Required actual status column '{ACTUAL_STATUS_COL}' not found.")


        # --- Plotting Logic ---
        plt.style.use('seaborn-v0_8-darkgrid')
        fig, ax = plt.subplots(figsize=(8, 4.5))

        # == CHART TYPE: Performance Metrics Bar Chart ==
        if chart_type == 'metrics_bar':
            # (Logic for metrics bar remains the same - uses *args)
            if len(args) != 4: raise ValueError("Metrics bar chart requires 4 values.")
            metric_names = ['Accuracy', 'Precision', 'Recall', 'F1-Score']
            try: metric_values = [float(v) for v in args]
            except ValueError: raise ValueError("Metric values must be numbers.")
            bars = ax.bar(metric_names, metric_values, color=metric_colors)
            ax.set_ylabel('Score'); ax.set_title('ADS Performance Metrics'); ax.set_ylim(0, 1.05); ax.bar_label(bars, fmt='%.3f')

        # == CHART TYPE: Status Distribution Pie Chart ==
        elif chart_type == 'status_pie':
            if ACTUAL_STATUS_COL not in df.columns: raise KeyError(ACTUAL_STATUS_COL)
            # Count based on string values
            status_counts = df[ACTUAL_STATUS_COL].value_counts()
            # Filter only expected labels and assign colors
            plot_labels = []
            plot_values = []
            plot_colors = []
            if 'legitimate' in status_counts.index:
                 plot_labels.append('Legitimate')
                 plot_values.append(status_counts['legitimate'])
                 plot_colors.append(color_legit)
            if 'phishing' in status_counts.index:
                 plot_labels.append('Phishing')
                 plot_values.append(status_counts['phishing'])
                 plot_colors.append(color_phish)

            if plot_values:
                ax.pie(plot_values, labels=plot_labels, colors=plot_colors, autopct='%1.1f%%', startangle=90)
                ax.set_title('URL Status Distribution (Actual)')
            else: print("Warning: No 'legitimate' or 'phishing' data found for status pie chart.", file=sys.stderr)


        # == CHART TYPE: URL Length Histogram ==
        elif chart_type == 'url_len_hist':
            if LEN_URL_COL not in df.columns or ACTUAL_STATUS_COL not in df.columns: raise KeyError(f'{LEN_URL_COL} or {ACTUAL_STATUS_COL}')
            # Filter based on string values
            legit_lengths = df[df[ACTUAL_STATUS_COL] == 'legitimate'][LEN_URL_COL].dropna()
            phish_lengths = df[df[ACTUAL_STATUS_COL] == 'phishing'][LEN_URL_COL].dropna()
            max_len = 0
            if not legit_lengths.empty: max_len = max(max_len, legit_lengths.max())
            if not phish_lengths.empty: max_len = max(max_len, phish_lengths.max())
            max_len = max(max_len, 1) # Ensure max_len is at least 1 if only 0 length urls exist
            bins = range(0, int(max_len) + 25, 25)
            ax.hist([legit_lengths, phish_lengths], bins=bins, label=['Legitimate', 'Phishing'], color=[color_legit, color_phish], alpha=0.7, edgecolor='gray')
            ax.set_title('URL Length Distribution by Status'); ax.set_xlabel('URL Length'); ax.set_ylabel('Frequency Count'); ax.legend(loc='upper right'); ax.grid(axis='y', alpha=0.7)

        # == CHART TYPE: Length vs Dots Scatter Plot ==
        elif chart_type == 'len_vs_dots_scatter':
            if LEN_URL_COL not in df.columns or DOTS_COL not in df.columns or ACTUAL_STATUS_COL not in df.columns: raise KeyError(f'{LEN_URL_COL}, {DOTS_COL} or {ACTUAL_STATUS_COL}')
            # Filter based on string values
            legit_data = df[df[ACTUAL_STATUS_COL] == 'legitimate'].dropna(subset=[LEN_URL_COL, DOTS_COL])
            phish_data = df[df[ACTUAL_STATUS_COL] == 'phishing'].dropna(subset=[LEN_URL_COL, DOTS_COL])
            ax.scatter(legit_data[LEN_URL_COL], legit_data[DOTS_COL], alpha=0.4, label='Legitimate', color=color_legit, s=10)
            ax.scatter(phish_data[LEN_URL_COL], phish_data[DOTS_COL], alpha=0.4, label='Phishing', color=color_phish, s=10)
            ax.set_title('URL Length vs. Number of Dots'); ax.set_xlabel('URL Length'); ax.set_ylabel('Number of Dots'); ax.legend(loc='upper right'); ax.grid(True, alpha=0.4)

        # == CHART TYPE: Average URL Length Bar Chart ==
        elif chart_type == 'avg_url_len_bar':
             if LEN_URL_COL not in df.columns or ACTUAL_STATUS_COL not in df.columns: raise KeyError(f'{LEN_URL_COL} or {ACTUAL_STATUS_COL}')
             # Group by string status
             avg_len = df.groupby(ACTUAL_STATUS_COL)[LEN_URL_COL].mean()
             # Map results back to fixed order/labels
             labels = ['Legitimate', 'Phishing']
             values = [avg_len.get('legitimate', 0), avg_len.get('phishing', 0)] # Get avg or 0
             colors = [color_legit, color_phish]
             bars = ax.bar(labels, values, color=colors)
             ax.set_ylabel('Average Length'); ax.set_title('Average URL Length by Status'); ax.bar_label(bars, fmt='%.1f')

        # == CHART TYPE: IP Usage Bar Chart ==
        elif chart_type == 'ip_usage_bar':
             # This chart assumes 'ip' column is 0 or 1
             if IP_COL not in df.columns or ACTUAL_STATUS_COL not in df.columns: raise KeyError(f'{IP_COL} or {ACTUAL_STATUS_COL}')
             # Filter where ip == 1, then count by string status
             ip_counts = df[df[IP_COL] == 1][ACTUAL_STATUS_COL].value_counts()
             labels = ['Legitimate', 'Phishing']
             values = [ip_counts.get('legitimate', 0), ip_counts.get('phishing', 0)] # Get count or 0
             colors = [color_legit, color_phish]
             bars = ax.bar(labels, values, color=colors)
             ax.set_ylabel('Number of URLs'); ax.set_title('Count of URLs Using IP Address by Status'); ax.bar_label(bars)

        # --- Unknown Chart Type ---
        else:
            print(f"Error: Unknown chart type '{chart_type}'", file=sys.stderr)
            plt.close(fig); return False

        # --- Save and Close ---
        fig.tight_layout()
        fig.savefig(output_image_path, format='png', bbox_inches='tight', dpi=96)
        plt.close(fig) # Free memory
        return True

    except FileNotFoundError: print(f"Error: Input CSV not found at {csv_path}", file=sys.stderr); return False
    except (KeyError, ValueError) as data_e: print(f"Data/Column Error for chart '{chart_type}': {data_e}", file=sys.stderr); plt.close(fig); return False
    except Exception as e: print(f"Error generating chart '{chart_type}': {e}", file=sys.stderr); plt.close(fig); return False


# --- Main execution block ---
if __name__ == "__main__":
    # Expecting: script_name.py csv_path output_image_path chart_type [metric_values...]
    if len(sys.argv) < 4:
        print("Usage: python generate_chart.py <csv_path> <output_image_path> <chart_type> [metric_values...]", file=sys.stderr)
        sys.exit(1)

    csv_file = sys.argv[1]
    image_file = sys.argv[2]
    type_of_chart = sys.argv[3]
    extra_args = sys.argv[4:] # Capture potential metric values

    if not generate_chart(csv_file, image_file, type_of_chart, *extra_args):
        sys.exit(1) # Exit with error code if chart generation failed

    sys.exit(0) # Exit successfully
