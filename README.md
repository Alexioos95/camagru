# camagru

Site internet Instagram-like en PHP, permettant de publier des montages photos réalisés à partir d'une capture de webcam ou une image téléversée et des éléments prédéfinis.

## Usage

Créer un fichier ```.env``` avec les variables nécessaires à la racine du projet, et ```make``` afin de lancer les Dockers. Le serveur Apache tournera sur ```https://<DUMP>:8443```.

## Fonctionnalités
- Gestion de sessions utilisateurs (inscription, connexion, récupération de mot de passe oublié, modification d'informations, et cookies)
- Envoi de mails de confirmation d'inscription, réinitialisation de mot de passe et notifications de nouveaux commentaires
- Galerie d'image publique en défilement infini avec interactions sociales (commentaires et likes)
- Montage photo avec incrustations de filtres déplaçables et supprimables
- Capture webcam et upload d'image
- Suppression d'une photo par l'utilisateur

## Demo
<img src="https://i.imgur.com/sVGwak9.gif" alt="Login" width="500"> <img src="https://i.imgur.com/2O5Y9Fg.gif" alt="Interaction sociales" width="500">  
<img src="https://i.imgur.com/FN1XCam.gif" alt="Galerie avec scroll infini" width="500"> <img src="https://i.imgur.com/U2TjMik.gif" alt="Montage photo" width="500">
