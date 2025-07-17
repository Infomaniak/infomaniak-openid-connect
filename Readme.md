# OpenID Connect Infomaniak Client

## Description

Le plugin OpenID Connect Infomaniak Client permet d'intégrer facilement l'authentification OAuth2 avec Infomaniak dans votre site WordPress. Grâce à ce plugin, les utilisateurs peuvent se connecter à votre site WordPress en utilisant leurs identifiants Infomaniak, ce qui simplifie le processus d'authentification et renforce la sécurité.

## Fonctionnalités

- Authentification des utilisateurs via les comptes Infomaniak
- Configuration simplifiée de l'intégration OAuth2/OpenID Connect
- Personnalisation du bouton de connexion
- Création automatique de comptes WordPress liés aux comptes Infomaniak
- Compatibilité avec les rôles et autorisations WordPress existants
- Journal des options pour le débogage

## Installation

1. Téléchargez le plugin et extrayez son contenu dans le répertoire `/wp-content/plugins/` de votre site WordPress
2. Activez le plugin via le menu 'Extensions' dans WordPress
3. Accédez aux paramètres du plugin via 'Réglages > OpenID Connect Infomaniak'

## Configuration

### Prérequis

Avant de configurer le plugin, vous devez créer une application OAuth2 dans votre espace Infomaniak :

1. Connectez-vous à votre compte Infomaniak
2. Accédez à la section de gestion des API et applications
3. Créez une nouvelle application OAuth2
4. Notez l'ID client et le secret client qui vous seront fournis
5. Configurez l'URL de redirection vers : `https://votre-site.com/openid-connect-authorize`

### Paramètres du plugin

Accédez à la page de configuration du plugin (Réglages > OpenID Connect Infomaniak) et renseignez les informations suivantes :

- **ID Client** : L'identifiant de votre application OAuth2 Infomaniak
- **Secret Client** : Le secret de votre application OAuth2 Infomaniak
- **URL d'autorisation** : URL du point de terminaison d'autorisation d'Infomaniak
- **URL du jeton** : URL du point de terminaison du jeton d'Infomaniak
- **URL des informations utilisateur** : URL du point de terminaison des informations utilisateur d'Infomaniak
- **Portée de l'authentification** : Les scopes OAuth2 requis (généralement "openid email profile")

## Utilisation

Une fois configuré, le plugin ajoutera automatiquement un bouton "Se connecter avec Infomaniak" sur votre formulaire de connexion WordPress. Les utilisateurs pourront cliquer sur ce bouton pour être redirigés vers la page d'authentification Infomaniak.

Après une authentification réussie, les utilisateurs seront redirigés vers votre site WordPress et automatiquement connectés. Si c'est leur première connexion, un compte WordPress sera créé automatiquement avec les informations de leur profil Infomaniak.

## Personnalisation

Le plugin offre plusieurs options de personnalisation :

- Texte et apparence du bouton de connexion
- Comportement de redirection après authentification
- Création et mise à jour automatique des comptes utilisateurs
- Mappage des informations utilisateur entre Infomaniak et WordPress

## Dépannage

Si vous rencontrez des problèmes avec le plugin :

1. Vérifiez que l'ID client et le secret client sont correctement saisis
2. Assurez-vous que l'URL de redirection est correctement configurée dans l'application Infomaniak
3. Consultez le journal des options du plugin pour plus d'informations sur les erreurs potentielles
4. Vérifiez que les points de terminaison OAuth2 d'Infomaniak sont accessibles depuis votre serveur

## Contribution

Les contributions à ce plugin sont les bienvenues ! N'hésitez pas à soumettre des pull requests ou à signaler des problèmes via le dépôt GitHub du projet.

## Licence

Ce plugin est distribué sous licence GPL v2 ou ultérieure.

## Crédits

Ce plugin est basé sur la bibliothèque OpenID Connect Generic et a été adapté spécifiquement pour l'intégration avec Infomaniak.
