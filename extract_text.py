import sys
import os
from PyPDF2 import PdfReader
import pytesseract
from PIL import Image
import docx
import re

def clean_text(text):
    # Replace problematic characters and normalize text
    text = text.encode('ascii', 'replace').decode('ascii')
    # Remove multiple spaces and newlines
    text = re.sub(r'\s+', ' ', text)
    # Split into lines for better readability
    text = '\n'.join(text.split('. '))
    return text.strip()

def extract_from_pdf(file_path):
    try:
        reader = PdfReader(file_path)
        text = []
        for page in reader.pages:
            page_text = page.extract_text()
            if page_text:
                text.append(page_text)
        
        if not text:
            return "No text could be extracted from PDF"
            
        combined_text = ' '.join(text)
        return clean_text(combined_text)
    except Exception as e:
        return f"Error extracting PDF: {str(e)}"

def extract_from_image(file_path):
    try:
        pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'
        image = Image.open(file_path)
        text = pytesseract.image_to_string(image)
        return clean_text(text)
    except Exception as e:
        return f"Error extracting image: {str(e)}"

def extract_from_docx(file_path):
    try:
        doc = docx.Document(file_path)
        text = '\n'.join([para.text for para in doc.paragraphs if para.text.strip()])
        return clean_text(text)
    except Exception as e:
        return f"Error extracting DOCX: {str(e)}"

def main():
    if len(sys.argv) != 3:
        print("Usage: python extract_text.py <file_path> <file_extension>")
        return

    file_path = sys.argv[1]
    file_ext = sys.argv[2].lower()

    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        return

    try:
        if file_ext == 'pdf':
            text = extract_from_pdf(file_path)
        elif file_ext in ['jpg', 'jpeg', 'png', 'tiff']:
            text = extract_from_image(file_path)
        elif file_ext == 'docx':
            text = extract_from_docx(file_path)
        else:
            text = f"Unsupported file type: {file_ext}"

        # Ensure text is properly encoded for output
        print(text)

    except Exception as e:
        print(f"Error processing file: {str(e)}")

if __name__ == "__main__":
    main()