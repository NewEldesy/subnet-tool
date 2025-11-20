# subnet-tool

Petit outil PHP pour générer des sous-réseaux IPv4 à partir d'un réseau source et d'un préfixe CIDR.

## Contenu du dépôt

Fichiers présents dans cet espace de travail :

- `index.php` — Interface web principale (formulaire JS + affichage des résultats et de l'historique de la session).
- `generate.php` — Endpoint POST qui calcule les sous-réseaux demandés, enregistre le résultat en base et renvoie du JSON.
- `history.php` — Endpoint JSON qui renvoie la liste des dernières entrées (`history`) pour la session en cours.
- `view_history.php` — Endpoint JSON qui renvoie le résultat détaillé (liste des sous-réseaux) pour une entrée historique donnée.

Fichiers référencés par le code (à vérifier/localiser) :

- `config.php` — Fichier de configuration et connexion PDO à la base de données (référencé par `generate.php`, `history.php`, `view_history.php`).
- `export.php` — Endpoint attendu pour exporter l'historique au format CSV (référencé depuis `index.php`).
- `clear_history.php` — Endpoint attendu pour supprimer l'historique de la session (référencé depuis `index.php`).

Remarque : les trois fichiers ci‑dessous sont mentionnés par `index.php` mais ne sont pas fournis parmi les fichiers listés plus haut dans cet espace de travail. Si vous les avez ailleurs, placez-les à la racine du projet.

## Description rapide des composants

- Frontend : `index.php` délivre le HTML + JavaScript qui :
	- Envoie une requête POST JSON vers `generate.php` pour calculer les sous-réseaux.
	- Charge l'historique via `history.php` et affiche le détail via `view_history.php`.
	- Propose des actions d'export (`export.php`) et de suppression (`clear_history.php`).

- Backend :
	- `generate.php` : valide les entrées (IP, CIDR, nombre de sous-réseaux), calcule les sous-réseaux, stocke le JSON des résultats dans la table `history` et retourne le JSON au client.
	- `history.php` : récupère les 20 dernières entrées pour `user_id` (stocké en session) et les renvoie.
	- `view_history.php` : renvoie les détails d'une entrée historique (colonne `results` décodée).

## Base de données

Le projet utilise une table `history` (via PDO). D'après les usages dans le code, le schéma attendu est le suivant (MySQL exemple) :

SQL (MySQL compatible) :

```sql
CREATE TABLE history (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ip VARCHAR(45) NOT NULL,
	cidr TINYINT UNSIGNED NOT NULL,
	num_subnets INT UNSIGNED NOT NULL,
	user_id VARCHAR(128) NOT NULL,
	results JSON NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

Si votre version de MySQL/MariaDB ne supporte pas le type JSON, utilisez `TEXT` pour `results` :

```sql
	results TEXT NOT NULL,
```

Colonne `results` : contient le JSON sérialisé des sous-réseaux tel qu'enregistré par `generate.php`.

Note sur `user_id` : le code courant initialise `$_SESSION['user_id']` avec `session_id()` dans `index.php` si absent, donc l'historique est lié à la session PHP.

## Démarrage rapide (développement)

1. Placer / configurer `config.php` pour qu'il fournisse une instance PDO dans `$pdo` connectée à votre base (MySQL/MariaDB).
2. Créer la table `history` comme indiqué ci-dessus.
3. Lancer un serveur PHP local à la racine du projet :

```powershell
php -S localhost:8000
```

4. Ouvrir `http://localhost:8000/index.php` dans votre navigateur.

## Remarques et conseils

- Vérifiez que `config.php` initialise correctement la variable `$pdo` et gère les erreurs PDO.
- Pensez à durcir la validation côté serveur si l'application est exposée publiquement (CSRF, rate limiting, validation plus stricte des plages IP/CIDR, limites sur `num_subnets`).
- Sauvegardez régulièrement la base avant d'exécuter des tests qui insèrent beaucoup d'entrées.

## Aide / prochaines étapes possibles

- Ajouter `export.php` et `clear_history.php` si vous souhaitez fournir les fonctionnalités d'export et de suppression qui sont déjà référencées dans `index.php`.
- Ajouter des tests unitaires pour la fonction de génération des sous-réseaux (extraction dans une fonction réutilisable/testable).
