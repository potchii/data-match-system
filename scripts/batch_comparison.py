#!/usr/bin/env python3
"""
Dynamic Field Extraction and Batch Comparison Tool
Extracts dynamic fields from CSV/XLSX files and enables batch-to-batch comparison
"""

import pandas as pd
import json
import sys
from pathlib import Path
from typing import Dict, List, Set, Any
from datetime import datetime

class DynamicFieldExtractor:
    """Extract and compare dynamic fields across batches"""
    
    # Core fields that should NOT be treated as dynamic
    CORE_FIELDS = {
        'uid', 'regsno', 'registration_no',
        'surname', 'lastname', 'last_name',
        'firstname', 'first_name', 'fname',
        'secondname', 'second_name',
        'middlename', 'middle_name', 'mname',
        'suffix', 'extension', 'ext',
        'dob', 'birthday', 'birthdate', 'date_of_birth',
        'sex', 'gender',
        'status', 'civil_status', 'civilstatus',
        'address', 'street',
        'city',
        'barangay', 'brgydescription',
        'province'
    }
    
    def __init__(self):
        self.batches = {}
    
    def normalize_column_name(self, col: str) -> str:
        """Normalize column names to lowercase with underscores"""
        return col.lower().strip().replace(' ', '_').replace('-', '_')
    
    def is_core_field(self, col: str) -> bool:
        """Check if column is a core field"""
        normalized = self.normalize_column_name(col)
        return normalized in self.CORE_FIELDS
    
    def read_file(self, filepath: str, encoding: str = 'utf-8') -> pd.DataFrame:
        """
        Read CSV or XLSX file with error handling
        
        Args:
            filepath: Path to the file
            encoding: File encoding (default: utf-8)
            
        Returns:
            pandas DataFrame
        """
        path = Path(filepath)
        
        if not path.exists():
            raise FileNotFoundError(f"File not found: {filepath}")
        
        try:
            if path.suffix.lower() == '.csv':
                # Try different delimiters
                for delimiter in [',', ';', '\t', '|']:
                    try:
                        df = pd.read_csv(filepath, encoding=encoding, delimiter=delimiter)
                        if len(df.columns) > 1:  # Valid if more than 1 column
                            print(f"✓ CSV read successfully with delimiter '{delimiter}'")
                            return df
                    except:
                        continue
                
                # If all delimiters fail, try with default
                df = pd.read_csv(filepath, encoding=encoding)
                
            elif path.suffix.lower() in ['.xlsx', '.xls']:
                df = pd.read_excel(filepath)
                print(f"✓ Excel file read successfully")
                
            else:
                raise ValueError(f"Unsupported file format: {path.suffix}")
            
            return df
            
        except UnicodeDecodeError:
            # Try different encodings
            for enc in ['latin-1', 'iso-8859-1', 'cp1252']:
                try:
                    print(f"Trying encoding: {enc}")
                    return pd.read_csv(filepath, encoding=enc)
                except:
                    continue
            raise ValueError(f"Could not decode file with any common encoding")
    
    def extract_dynamic_fields(self, df: pd.DataFrame) -> Dict[str, List[str]]:
        """
        Extract dynamic (non-core) fields from DataFrame
        
        Returns:
            Dict with 'core_fields' and 'dynamic_fields' lists
        """
        all_columns = df.columns.tolist()
        
        core_fields = []
        dynamic_fields = []
        
        for col in all_columns:
            if self.is_core_field(col):
                core_fields.append(col)
            else:
                dynamic_fields.append(col)
        
        return {
            'core_fields': core_fields,
            'dynamic_fields': dynamic_fields,
            'total_columns': len(all_columns)
        }
    
    def process_batch(self, batch_id: str, filepath: str) -> Dict[str, Any]:
        """
        Process a batch file and extract dynamic fields
        
        Args:
            batch_id: Unique identifier for this batch
            filepath: Path to the CSV/XLSX file
            
        Returns:
            Dict containing batch metadata and dynamic field data
        """
        print(f"\n{'='*60}")
        print(f"Processing Batch: {batch_id}")
        print(f"File: {filepath}")
        print(f"{'='*60}")
        
        # Read file
        df = self.read_file(filepath)
        
        # Extract field information
        field_info = self.extract_dynamic_fields(df)
        
        # Convert DataFrame to records with dynamic fields
        records = []
        for idx, row in df.iterrows():
            record = {
                'row_number': idx + 1,
                'core_fields': {},
                'dynamic_fields': {}
            }
            
            for col in df.columns:
                value = row[col]
                
                # Skip NaN values
                if pd.isna(value):
                    continue
                
                # Convert to JSON-serializable type
                if isinstance(value, (pd.Timestamp, datetime)):
                    value = value.isoformat()
                elif isinstance(value, (int, float)):
                    value = float(value) if isinstance(value, float) else int(value)
                else:
                    value = str(value)
                
                if self.is_core_field(col):
                    record['core_fields'][col] = value
                else:
                    normalized_key = self.normalize_column_name(col)
                    record['dynamic_fields'][normalized_key] = value
            
            records.append(record)
        
        # Create batch summary
        batch_data = {
            'batch_id': batch_id,
            'filepath': filepath,
            'processed_at': datetime.now().isoformat(),
            'total_rows': len(df),
            'field_summary': field_info,
            'records': records
        }
        
        # Store in memory
        self.batches[batch_id] = batch_data
        
        # Print summary
        print(f"\n✓ Batch processed successfully")
        print(f"  Total rows: {len(df)}")
        print(f"  Core fields: {len(field_info['core_fields'])}")
        print(f"  Dynamic fields: {len(field_info['dynamic_fields'])}")
        print(f"\nCore fields found:")
        for field in field_info['core_fields']:
            print(f"  - {field}")
        print(f"\nDynamic fields found:")
        for field in field_info['dynamic_fields']:
            print(f"  - {field}")
        
        return batch_data
    
    def compare_batches(self, batch_a_id: str, batch_b_id: str) -> Dict[str, Any]:
        """
        Compare two batches based on their dynamic fields
        
        Args:
            batch_a_id: ID of first batch
            batch_b_id: ID of second batch
            
        Returns:
            Comparison results
        """
        if batch_a_id not in self.batches:
            raise ValueError(f"Batch '{batch_a_id}' not found")
        if batch_b_id not in self.batches:
            raise ValueError(f"Batch '{batch_b_id}' not found")
        
        batch_a = self.batches[batch_a_id]
        batch_b = self.batches[batch_b_id]
        
        # Get dynamic field keys from both batches
        dynamic_a = set(batch_a['field_summary']['dynamic_fields'])
        dynamic_b = set(batch_b['field_summary']['dynamic_fields'])
        
        # Find shared and unique fields
        shared_fields = dynamic_a & dynamic_b
        only_in_a = dynamic_a - dynamic_b
        only_in_b = dynamic_b - dynamic_a
        
        comparison = {
            'batch_a': {
                'id': batch_a_id,
                'total_rows': batch_a['total_rows'],
                'dynamic_fields': list(dynamic_a)
            },
            'batch_b': {
                'id': batch_b_id,
                'total_rows': batch_b['total_rows'],
                'dynamic_fields': list(dynamic_b)
            },
            'comparison': {
                'shared_dynamic_fields': list(shared_fields),
                'only_in_batch_a': list(only_in_a),
                'only_in_batch_b': list(only_in_b),
                'total_shared': len(shared_fields),
                'total_unique_a': len(only_in_a),
                'total_unique_b': len(only_in_b)
            }
        }
        
        return comparison
    
    def export_batch_json(self, batch_id: str, output_path: str):
        """Export batch data to JSON file"""
        if batch_id not in self.batches:
            raise ValueError(f"Batch '{batch_id}' not found")
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(self.batches[batch_id], f, indent=2, ensure_ascii=False)
        
        print(f"\n✓ Batch '{batch_id}' exported to: {output_path}")
    
    def export_comparison_json(self, batch_a_id: str, batch_b_id: str, output_path: str):
        """Export batch comparison to JSON file"""
        comparison = self.compare_batches(batch_a_id, batch_b_id)
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(comparison, f, indent=2, ensure_ascii=False)
        
        print(f"\n✓ Comparison exported to: {output_path}")


def main():
    """Main execution function"""
    print("="*60)
    print("Dynamic Field Extraction & Batch Comparison Tool")
    print("="*60)
    
    extractor = DynamicFieldExtractor()
    
    # Example usage
    if len(sys.argv) < 2:
        print("\nUsage:")
        print("  python batch_comparison.py <file1.csv> [file2.csv]")
        print("\nExample:")
        print("  python batch_comparison.py batch_a.csv batch_b.csv")
        sys.exit(1)
    
    # Process first batch
    batch_a_path = sys.argv[1]
    batch_a_data = extractor.process_batch('Batch_A', batch_a_path)
    
    # Export first batch
    output_a = f"batch_a_output.json"
    extractor.export_batch_json('Batch_A', output_a)
    
    # Process second batch if provided
    if len(sys.argv) > 2:
        batch_b_path = sys.argv[2]
        batch_b_data = extractor.process_batch('Batch_B', batch_b_path)
        
        # Export second batch
        output_b = f"batch_b_output.json"
        extractor.export_batch_json('Batch_B', output_b)
        
        # Compare batches
        print(f"\n{'='*60}")
        print("Batch Comparison")
        print(f"{'='*60}")
        
        comparison = extractor.compare_batches('Batch_A', 'Batch_B')
        
        print(f"\nShared dynamic fields ({len(comparison['comparison']['shared_dynamic_fields'])}):")
        for field in comparison['comparison']['shared_dynamic_fields']:
            print(f"  ✓ {field}")
        
        print(f"\nOnly in Batch A ({len(comparison['comparison']['only_in_batch_a'])}):")
        for field in comparison['comparison']['only_in_batch_a']:
            print(f"  → {field}")
        
        print(f"\nOnly in Batch B ({len(comparison['comparison']['only_in_batch_b'])}):")
        for field in comparison['comparison']['only_in_batch_b']:
            print(f"  → {field}")
        
        # Export comparison
        extractor.export_comparison_json('Batch_A', 'Batch_B', 'batch_comparison.json')
    
    print(f"\n{'='*60}")
    print("✓ Processing complete!")
    print(f"{'='*60}\n")


if __name__ == '__main__':
    main()
