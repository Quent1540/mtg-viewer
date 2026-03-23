**EXERCICE 0**

Le processus étant de plus en plus lent selon le nombre de cartes à importer, j'ai utilisé la fonction array_flip() sur le tableau des UUIDS existants pour transformer les valeurs en clés


**EXERCICE 1**

(voir src/Eventlistener/ApiLogListener.php)


- Ce listener est un composant Symfony qui s'exécute sur l'événement `KernelEvents::REQUEST`.
- À chaque requête HTTP il vérifie si le chemin commence par `/api`.
- Si oui, il écrit une ligne de log contenant la méthode HTTP et le chemin (ex: `GET /api/cards`).

**EXERCICE 2**

Backend :

- Méthode `searchByName` dans `CardRepository`, limitée à 20 résultats.

Frontend :

- Barre de recherche sans bouton : la requête se lance automatiquement.
- Ajout d'une temporisation (debounce ~350ms) pour éviter de spammer l'API à chaque frappe(fluidifie l'application).
- La recherche ne s'exécute qu'à partir de 3 caractères pour limiter les résultats non pertinents.


