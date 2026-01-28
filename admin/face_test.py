from facenet_pytorch import MTCNN, InceptionResnetV1
import cv2
import os
import numpy as np
import torch
import sys
import glob

if len(sys.argv) < 3:
    print("Error: id_user atau path gambar tidak diberikan!")
    sys.exit(1)

input_id_user = sys.argv[1]
image_path = sys.argv[2]

mtcnn = MTCNN()
facenet = InceptionResnetV1(pretrained='vggface2').eval()

faces_dir = os.path.join(os.path.dirname(_file_), 'faces')
user_folder = os.path.join(faces_dir, input_id_user)
known_face_encodings = []

file_gambar = glob.glob(os.path.join(user_folder, '*.jpg'))

if len(file_gambar) == 0:
    print(f"No reference face image found for user {input_id_user}.")
    sys.exit(1)

for file in file_gambar:
    img = cv2.imread(file)
    faces, _ = mtcnn.detect(img)
    if faces is not None:
        for x1, y1, x2, y2 in faces:
            face_crop = img[int(y1):int(y2), int(x1):int(x2)]
            face_crop_resized = cv2.resize(face_crop, (160, 160))
            face_tensor = np.expand_dims(face_crop_resized / 255.0, axis=0)
            embedding = facenet(torch.FloatTensor(face_tensor).permute(0, 3, 1, 2)).detach().numpy()
            known_face_encodings.append(embedding)

img = cv2.imread(image_path)
faces, _ = mtcnn.detect(img)

if faces is None:
    print(f"No faces detected in {image_path}!")
    sys.exit(1)

for x1, y1, x2, y2 in faces:
    face_crop = img[int(y1):int(y2), int(x1):int(x2)]
    face_crop_resized = cv2.resize(face_crop, (160, 160))
    face_tensor = np.expand_dims(face_crop_resized / 255.0, axis=0)
    embedding_to_check = facenet(torch.FloatTensor(face_tensor).permute(0, 3, 1, 2)).detach().numpy()

    distances = [np.linalg.norm(embedding_to_check - known) for known in known_face_encodings]
    min_distance = min(distances)

    if min_distance < 0.8:  # Threshold diperbesar untuk akurasi lebih baik
        print("Face verified successfully!")
        sys.exit(0)

print("Wajah tidak dikenal!")
sys.exit(1)