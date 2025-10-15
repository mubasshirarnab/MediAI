import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import RandomForestClassifier
import joblib

# Load the dataset
df = pd.read_csv('framingham.csv')

# Drop rows with missing values
df = df.dropna()

# Drop 'education' column
df = df.drop(columns=['education'])

# Feature and label separation
X = df.drop(['TenYearCHD'], axis=1)
y = df['TenYearCHD']

# Handle categorical variables
X = pd.get_dummies(X, drop_first=True)  # e.g., 'male' becomes binary

# Get feature names after one-hot encoding
feature_names = X.columns.tolist()

# Train-test split (stratified)
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, stratify=y, random_state=42)

# Feature scaling
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)

# Train the model
model = RandomForestClassifier(n_estimators=200, random_state=42)
model.fit(X_train_scaled, y_train)

# Save both the model and scaler
model_data = {
    'model': model,
    'scaler': scaler,
    'feature_names': feature_names
}

joblib.dump(model_data, 'heart_risk_model.joblib')
print("Model saved successfully!")
