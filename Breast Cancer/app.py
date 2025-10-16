from flask import Flask, request, jsonify
import os
from helper import *
from flask_cors import CORS


app = Flask(__name__)
CORS(app)


@app.route('/detect-breast', methods=['POST'])
def detect_breast():
    data = request.get_json()

    if not data or 'image_path' not in data:
        return jsonify({'error': 'Missing image_path'}), 400

    image_path = data['image_path']

    # Optional: Validate if file exists
    if not os.path.isfile(image_path):
        return jsonify({'error': f'File not found: {image_path}'}), 404

    # Call the detect function from helper.py
    result = detect(image_path, "breast")

    return jsonify({'result': result}), 202

@app.route('/detect-tumor', methods=['POST'])
def detect_tumor():
    data = request.get_json()

    if not data or 'image_path' not in data:
        return jsonify({'error': 'Missing image_path'}), 400

    image_path = data['image_path']

    # Optional: Validate if file exists
    if not os.path.isfile(image_path):
        return jsonify({'error': f'File not found: {image_path}'}), 404

    # Call the detect function from helper.py
    result = detect(image_path, "tumor")

    return jsonify({'result': result}), 202

if __name__ == '__main__':
    app.run(debug=True)