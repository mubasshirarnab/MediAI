from keras.models import load_model
from keras.preprocessing.image import load_img, img_to_array
import numpy as np


model_path_breast = "modelBreast.h5"
model_path_tumor = "model.h5"

print(" Model loaded successfully!")


# Class labels
class_labels = ['benign', 'malignant', 'normal' ]

def detect(img_path, model_name, image_size=128):

    try:
        if model_name == "breast":
            model = load_model(model_path_breast)
        else:
            model = load_model(model_path_tumor)

        # Load and preprocess the image
        img = load_img(img_path, target_size=(image_size, image_size))
        img_array = img_to_array(img) / 255.0  # Normalize pixel values
        img_array = np.expand_dims(img_array, axis=0)  # Add batch dimension

        # Make a prediction
        predictions = model.predict(img_array)
        predicted_class_index = np.argmax(predictions, axis=1)[0]
        confidence_score = np.max(predictions, axis=1)[0]

        # Determine the class
        if class_labels[predicted_class_index] == 'normal':
            result = "No Breast Cancer"
            # print(result)
        else:
            result =  f"Breast Cancer: {class_labels[predicted_class_index]}"
            # print(result)

        # Display the image with the prediction
        # plt.imshow(load_img(img_path))
        # plt.axis('off')
        # plt.title(f"{result} (Confidence: {confidence_score * 100:.2f}%)")
        # plt.show()

    except Exception as e:
        print("Error processing the image:", str(e))

    return result

# image_path = "Test data\\Breast\\benign\\benign (25)_mask.png"
# res = detect(image_path, model)

# print(res)