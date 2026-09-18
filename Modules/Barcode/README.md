# Barcode Module

## Overview
Generates and prints barcodes and labels for products. Supports various barcode formats and batch printing for inventory labeling.

## Controllers
- **BarcodeController** - Barcode generation, preview, and print actions

## Services
- **BarcodeService** - Barcode image generation logic, format handling, and print layout composition

## Form Requests
- **BarcodeSearchRequest** - Validates search query (min 2 chars)
- **GenerateBarcodeRequest** - Validates barcode generation (product IDs, quantities, barcode type, label options)
- **PrintBarcodeRequest** - Validates print request (product IDs, quantities)

## Routes
- **Barcode Generation** - Generate barcodes for selected products
- **Barcode Printing** - Print barcode labels in configurable layouts (single, sheet)

## Settings / Configuration
- Barcode format (Code 128, EAN-13, etc.)
- Label dimensions and layout (labels per row, paper size)
- Information displayed on labels (product name, price, SKU)

## Dependencies
- **Product** - Reads product data (SKU, name, price) to generate barcode labels
