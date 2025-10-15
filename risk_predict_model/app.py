from flask import Flask, request, jsonify
import joblib
import numpy as np
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Load the model and feature names
model_data = joblib.load('heart_risk_model.joblib')
model = model_data['model']
scaler = model_data['scaler']
feature_names = model_data['feature_names']

# Create a mapping between frontend field names and model feature names
feature_mapping = {
    'age': 'age',
    'sex': {'male': 'male_1'},  # Will be 1 for male, 0 for female
    'current_smoker': {'yes': 1, 'no': 0},
    'cigs_per_day': 'cigsPerDay',
    'bp_medications': {'yes': 1, 'no': 0},
    'prevalent_stroke': {'yes': 1, 'no': 0},
    'prevalent_hyp': {'yes': 1, 'no': 0},
    'diabetes': {'yes': 1, 'no': 0},
    'cholesterol': 'totChol',
    'systolic_bp': 'sysBP',
    'heart_rate': 'heartRate',
    'glucose': 'glucose'
}

@app.route('/predict', methods=['POST'])
def predict():
    try:
        # Get data from request
        data = request.get_json()
        
        # Create a dictionary to store the feature values
        features = {}
        
        # Process each field from the frontend
        for field, value in data.items():
            if field in feature_mapping:
                mapping = feature_mapping[field]
                if isinstance(mapping, dict):
                    # Handle categorical variables
                    if field == 'sex':
                        features['male_1'] = 1 if value.lower() == 'male' else 0
                    else:
                        features[field] = 1 if value.lower() == 'yes' else 0
                else:
                    # Handle numerical variables
                    features[mapping] = float(value)
        
        # Create feature array in the correct order
        feature_array = []
        for feature in feature_names:
            feature_array.append(features.get(feature, 0))
        
        # Scale features
        features_scaled = scaler.transform([feature_array])
        
        # Make prediction
        prediction = model.predict(features_scaled)[0]
        probability = model.predict_proba(features_scaled)[0][1]
        
        # Return result
        return jsonify({
            'prediction': 'High Risk' if prediction == 1 else 'Low Risk',
            'probability': float(probability),
            'risk_percentage': f"{probability * 100:.1f}%"
        })
        
    except Exception as e:
        return jsonify({'error': f'An error occurred: {str(e)}'}), 500

if __name__ == '__main__':
    app.run(debug=True)
