<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
 protected function traitementSelect(string $table, ?array $champs) : ?array{
    switch($table){  
        case "livre" :
            return $this->selectAllLivres();
        case "dvd" :
            return $this->selectAllDvd();
        case "revue" :
            return $this->selectAllRevues();
        case "exemplaire" :
            return $this->selectExemplairesRevue($champs);
        case "genre" :
        case "public" :
        case "rayon" :
        case "etat" :
            return $this->selectTableSimple($table);
        case "suivi" :
            return $this->selectTableSimple($table);
        case "commandeslivre" :
            return $this->selectCommandesLivre($champs);
        case "commandesdvd" :
            return $this->selectCommandesDvd($champs);
        case "commandesrevue" :
            return $this->selectCommandesRevue($champs);
        case "revuesexpirant" :
            return $this->selectRevuesExpirant();
        case "utilisateur" :
            return $this->selectUtilisateur($champs);
        default:
            return $this->selectTuplesOneTable($table, $champs);
    }	
}
    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "commande" :
                return $this->insertCommande($champs);
            case "abonnement" :
                return $this->insertAbonnement($champs);
            default:                    
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "commandedocument" :
                return $this->updateSuiviCommande($id, $champs);
            default:                    
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "commande" :
                return $this->deleteCommande($champs);
            case "abonnement" :
                return $this->deleteAbonnement($champs);
            default:                    
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string|null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les commandes d'un livre avec les infos de suivi
     * @param array|null $champs doit contenir 'id' du livre
     * @return array|null
     */
    private function selectCommandesLivre(?array $champs) : ?array{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select c.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idSuivi, s.libelle as suivi ";
        $requete .= "from commande c ";
        $requete .= "join commandedocument cd on c.id = cd.id ";
        $requete .= "join suivi s on s.id = cd.idSuivi ";
        $requete .= "where cd.idLivreDvd = :id ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les commandes d'un DVD avec les infos de suivi
     * @param array|null $champs doit contenir 'id' du DVD
     * @return array|null
     */
    private function selectCommandesDvd(?array $champs) : ?array{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select c.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idSuivi, s.libelle as suivi ";
        $requete .= "from commande c ";
        $requete .= "join commandedocument cd on c.id = cd.id ";
        $requete .= "join suivi s on s.id = cd.idSuivi ";
        $requete .= "where cd.idLivreDvd = :id ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les commandes d'une revue (abonnements)
     * @param array|null $champs doit contenir 'id' de la revue
     * @return array|null
     */
    private function selectCommandesRevue(?array $champs) : ?array{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select c.id, c.dateCommande, c.montant, a.dateFinAbonnement ";
        $requete .= "from commande c ";
        $requete .= "join abonnement a on c.id = a.id ";
        $requete .= "where a.idRevue = :id ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère les revues dont l'abonnement expire dans moins de 30 jours
     * @return array|null
     */
    private function selectRevuesExpirant() : ?array{
        $requete = "Select d.titre, a.dateFinAbonnement ";
        $requete .= "from abonnement a ";
        $requete .= "join document d on a.idRevue = d.id ";
        $requete .= "where a.dateFinAbonnement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ";
        $requete .= "order by a.dateFinAbonnement ASC";
        return $this->conn->queryBDD($requete);
    }

    /**
     * insère une commande dans commande et commandedocument
     * @param array|null $champs contient id, dateCommande, montant, nbExemplaire, idLivreDvd
     * @return int|null nombre de tuples ajoutés ou null si erreur
     */
    private function insertCommande(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        $champsCommande = [
            'id' => $champs['id'],
            'datecommande' => $champs['datecommande'] ?? $champs['dateCommande'],
            'montant' => $champs['montant']
        ];
        $result = $this->insertOneTupleOneTable('commande', $champsCommande);
        if(is_null($result)){
            return null;
        }
        $champsCommandeDoc = [
            'id' => $champs['id'],
            'nbExemplaire' => $champs['nbExemplaire'],
            'idLivreDvd' => $champs['idLivreDvd'],
            'idSuivi' => 'en cours'
        ];
        return $this->insertOneTupleOneTable('commandedocument', $champsCommandeDoc);
    }

    /**
     * insère un abonnement dans commande et abonnement
     * @param array|null $champs contient id, datecommande, montant, dateFinAbonnement, idRevue
     * @return int|null nombre de tuples ajoutés ou null si erreur
     */
    private function insertAbonnement(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        $champsCommande = [
            'id' => $champs['id'],
            'datecommande' => $champs['datecommande'],
            'montant' => $champs['montant']
        ];
        $result = $this->insertOneTupleOneTable('commande', $champsCommande);
        if(is_null($result)){
            return null;
        }
        $champsAbonnement = [
            'id' => $champs['id'],
            'dateFinAbonnement' => $champs['dateFinAbonnement'],
            'idRevue' => $champs['idRevue']
        ];
        return $this->insertOneTupleOneTable('abonnement', $champsAbonnement);
    }

    /**
     * modifie l'étape de suivi d'une commande
     * @param string|null $id id de la commande
     * @param array|null $champs contient idSuivi
     * @return int|null nombre de tuples modifiés ou null si erreur
     */
    private function updateSuiviCommande(?string $id, ?array $champs) : ?int{
        if(empty($champs) || is_null($id)){
            return null;
        }
        $champs['id'] = $id;
        $requete = "update commandedocument set idSuivi=:idSuivi where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * supprime une commande (trigger supprime aussi dans commandedocument)
     * @param array|null $champs contient id
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteCommande(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire = ['id' => $champs['id']];
        $requete = "delete from commande where id=:id;";
        return $this->conn->updateBDD($requete, $champNecessaire);
    }

  /**
 * supprime un abonnement
 * @param array|null $champs contient id
 * @return int|null nombre de tuples supprimés ou null si erreur
 */
private function deleteAbonnement(?array $champs) : ?int{
    if(empty($champs) || !array_key_exists('id', $champs)){
        return null;
    }
    $champNecessaire = ['id' => $champs['id']];
    // supprime d'abord dans abonnement (table fille)
    $requete = "delete from abonnement where id=:id;";
    $this->conn->updateBDD($requete, $champNecessaire);
    // puis supprime dans commande (table mère)
    $requete = "delete from commande where id=:id;";
    return $this->conn->updateBDD($requete, $champNecessaire);
}
/**
 * récupère un utilisateur par login et mot de passe
 * @param array|null $champs contient login et mdp
 * @return array|null
 */
private function selectUtilisateur(?array $champs) : ?array{
    if(empty($champs) || !array_key_exists('login', $champs)){
        return null;
    }
    $requete = "Select u.id, u.login, u.mdp, u.idService, s.libelle as service ";
    $requete .= "from Utilisateur u ";
    $requete .= "join Service s on s.id = u.idService ";
    $requete .= "where u.login = :login and u.mdp = :mdp";
    return $this->conn->queryBDD($requete, $champs);
}
}