<?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();//COMMMENCER LA NOUVELLE SESSION
    }

    if (!isset($_SESSION["user_id"])) {// SI LA PERSONNE S EST PAS CONNECTE AVANT
        header("Location: login.php");//ON L EMMENE SUR LA PAGE POUR SE CONNECTER
        exit();
    }

    $db_host = 'localhost';//ON RECUPERE LES INFO DE LA BDD
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (!$conn) {// SI ON EST PAS BIEN CONNECTE
        error_log("Erreur de connexion BDD: " . mysqli_connect_error()); //ON ANNONCE POUR POUVOIR AGIR
        $_SESSION['profile_error'] = "Erreur de connexion à la base de données";//ON PREVIENT LA PERSONNE
        header("Location: profil.php"); //ON LA REDIRIGIE SUR LA PAGE DU PROFIL
        exit();
    }
    mysqli_set_charset($conn, 'utf8');

    $user_id = $_SESSION["user_id"];//ON SE CONNCECTE AVEC L ID DE L UTILISATEUR
    $user_data = [];
    $form_errors = [];
    $profile_error_message = ''; 
    $profile_success_message = ''; 

    function safe_html($value) {
        return $value !== 0 ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';//ON VERIFIE ICI QU ON RECOIS TOUJOURS UNE CHAINE DE CARACTERE
    }
  
    $sql_fetch = "
        SELECT
            u.ID, u.Nom, u.Prenom, u.Email, u.TypeCompte,
            a.ID AS AdresseID, a.Adresse AS AdresseLigne, a.Ville, a.CodePostal, a.InfosComplementaires,
            uc.Telephone AS ClientTelephone, uc.CarteVitale,
            up.Telephone AS PersonnelTelephone, up.Description, up.Type AS Specialite
        FROM utilisateurs u
        LEFT JOIN utilisateurs_client uc ON u.ID = uc.ID AND u.TypeCompte = 'client'
        LEFT JOIN utilisateurs_personnel up ON u.ID = up.ID AND u.TypeCompte = 'personnel'
        LEFT JOIN adresse a ON (
            (u.TypeCompte = 'client' AND uc.ID_Adresse = a.ID) OR
            (u.TypeCompte = 'personnel' AND up.ID_Adresse = a.ID)
        )
        WHERE u.ID = ?
    ";//ICI ON PREND LES DONNEES DE LA PERSONNE CONNECTE 

    $stmt_fetch = mysqli_prepare($conn, $sql_fetch);//ON PREPARE ICI POUR LE SQL QUI ARRIVE
    if ($stmt_fetch) {//SI C EST BON
        mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);//ON REGARDE ID 
        mysqli_stmt_execute($stmt_fetch);//ON EXECUTRE LA DEMANDE
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);//ON RECUPERE
        $user_data = mysqli_fetch_assoc($result_fetch);
        mysqli_free_result($result_fetch);
        mysqli_stmt_close($stmt_fetch);//ON LIBERE ICI LA MEMEOIRE COMME POUR SOULAGER
    } else {
        error_log("Erreur " . mysqli_error($conn));//SINON  SI Y A UN PB ON PREVEINT LA PERSONNE
        $profile_error_message = "Erreur";//PREVENIR AVEC UN MESSAGE LE PERSONNE
    }

    if (empty($user_data) && empty($profile_error_message)) { //SI PA S DE MESSAEG ERREUR ET PAS PRIS DE DONNE
        $_SESSION['profile_error'] = "Impossible de charger les informations";//ON INDIQUE MESSAGE ERREUR
        mysqli_close($conn);//ON REFERME
        header("Location: profil.php");//RETOURNE SUR LA PAGE PROFIL
        exit();
    }
    $type = $user_data['TypeCompte'] ?? 0; //ON RETIENT LE TYPE DU COMPTE DE LA PERSONNE

    if ($_SERVER["REQUEST_METHOD"] == "POST") {//ON RECUP LES ODNNES
        $nom = trim($_POST['nom'] ?? $user_data['Nom']);//ON REGARDE POUR RETENIR LE NOM
        $prenom = trim($_POST['prenom'] ?? $user_data['Prenom']);//ON REGARDE POUR RETENIR LE PRENOM
        $email = trim($_POST['email'] ?? $user_data['Email']);//ON REGARDE POUR RETENIR LE MAIL

        $adresse_ligne = trim($_POST['adresse_ligne'] ?? $user_data['AdresseLigne']);//ON RETIENT ADRESSE TRIM POUR ACCEPTE ESPACE
        $ville = trim($_POST['ville'] ?? $user_data['Ville']);//ON RECUP LA VILLE
        $code_postal = trim($_POST['code_postal'] ?? $user_data['CodePostal']);//LE CDP
        $infos_complementaires = trim($_POST['infos_complementaires'] ?? $user_data['InfosComplementaires']);//SI AUTRES INFOS
        $adresse_id = $user_data['AdresseID'] ?? 0;

        $telephone = '';//INIT LE TEL
        $carte_vitale = '';//INIT LE NUM DE CARTE VITALE
        $description = '';//ON INTI LA DESCRIPTION
        $specialite = '';//ON INTI LA SPE

        if ($type === 'client') {//SI C EST UN CLIENT
            $telephone = trim($_POST['telephone'] ?? $user_data['ClientTelephone']);//ON RECUP SES INFO DE TEL
            $carte_vitale = trim($_POST['carte_vitale'] ?? $user_data['CarteVitale']);//ET DE CARTE VITALE
        } elseif ($type === 'personnel') {//SI C EST UN PERSONNEL
            $telephone = trim($_POST['telephone'] ?? $user_data['PersonnelTelephone']);//LE TELEPHONE
            $description = trim($_POST['description'] ?? $user_data['Description']);///SA DESCRIPTION DE METIER
            $specialite = trim($_POST['specialite'] ?? $user_data['Specialite']);//SA SPECIALITE
        }

        if (empty($nom)) $form_errors['nom'] = "Le nom est requis.";// VERIFIER QUE C EST BIEN INDIQUE
        if (empty($prenom)) $form_errors['prenom'] = "Le prénom est requis.";// VERIFIER QUE C EST BIEN INDIQUE
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $form_errors['email'] = "Email invalide.";//VERIFIER QUE LE FORMAT DE LA DRESSE MAIL EST BONNE SINON PREVENIR

        if (empty($adresse_ligne)) $form_errors['adresse_ligne'] = "L'adresse est requise.";// VERIFIER QUE C EST BIEN INDIQUE
        if (empty($ville)) $form_errors['ville'] = "La ville est requise.";// VERIFIER QUE C EST BIEN INDIQUE
        if (empty($code_postal) || !ctype_digit($code_postal) || strlen($code_postal) != 5) $form_errors['code_postal'] = "Code postal invalide.";//IL DOIT Y A VOIR 5 NUMERO POUR LE CDP VERIF SI BIEN FAIT

        if ($type === 'client' || $type === 'personnel') {//SI C EST UN CLIENT OU PERSONNEL
            if (empty($telephone) || !preg_match('/^0[67]\d{8}$/', $telephone)) {//LE NUMERO COMMENCE PAR UN 06 OU 07 AVEC BIEN SUR 10 NUMERO DEDANS. SOURCE:  https://www.php.net/manual/fr/function.preg-match.php
                $form_errors['telephone'] = "Numéro de téléphone invalide";//SINON C EST PAS BON
            }
        }
        if ($type === 'client') {//SI C EST UN CLIENT
            if (empty($carte_vitale) || !ctype_digit($carte_vitale) || strlen($carte_vitale) < 13 || strlen($carte_vitale) > 15) {//LE NUM DE LA CARTE VITALEE DOIT ETRE DE 14 CHIFFRE POUR MARCHER
                $form_errors['carte_vitale'] = "Numéro de carte vitale.";//SINON DIRE QUE Y A UN PB
            }
        }

        if (empty($form_errors)) {//SI Y A PAS DE PB
            mysqli_begin_transaction($conn);
            $all_queries_success = true;

            $sql_update_user = "UPDATE utilisateurs SET Nom = ?, Prenom = ?, Email = ? WHERE ID = ?";//ON SAUVEGARDE LES INFO ENREGISTREES
            $stmt_update_user = mysqli_prepare($conn, $sql_update_user);//ON RECUPERE LA BDD
            if ($stmt_update_user) {
                mysqli_stmt_bind_param($stmt_update_user, "sssi", $nom, $prenom, $email, $user_id);//ON LIE LES INFOS UTILES
                if (!mysqli_stmt_execute($stmt_update_user)) {
                    $all_queries_success = false;//SI CA MARCHE PAS AVEC UN PB
                    error_log("Échec maj utilisateurs: " . mysqli_stmt_error($stmt_update_user));//ON LE DIT A LA PERSONNE
                }
                mysqli_stmt_close($stmt_update_user);//ON REFERME
            } else {
                $all_queries_success = false;//SI CA MARCHE PAS
                error_log("Erreur préparation maj utilisateurs: " . mysqli_error($conn));//ON INDIQUE ERREUR
            }

            if ($all_queries_success) {
                if ($adresse_id) {//ON CHANGE INFO DE ADRESSE
                    $sql_update_address = "UPDATE adresse SET Adresse = ?, Ville = ?, CodePostal = ?, InfosComplementaires = ? WHERE ID = ?";//ON RECUPERER LES CHAMPS DE LA BDD
                    $stmt_update_address = mysqli_prepare($conn, $sql_update_address);//ON SE LIE A LA BDD UTILE
                    if ($stmt_update_address) {
                        mysqli_stmt_bind_param($stmt_update_address, "ssssi", $adresse_ligne, $ville, $code_postal, $infos_complementaires, $adresse_id);//ON RELIE LA DEMANDE AUX INFO DE LA BDD
                        if (!mysqli_stmt_execute($stmt_update_address)) {//SI CA EXECUTE PAS BIEN
                            $all_queries_success = false;//SI CA MARCHE PAS
                            error_log("Échec maj adresse: " . mysqli_stmt_error($stmt_update_address));//ON INDIQUE A LA PERSONNE L ERREUR
                        }
                        mysqli_stmt_close($stmt_update_address);//ON REFERME
                    } else {
                        $all_queries_success = false;//SI CA A  PAS BIEN MARCHE
                        error_log("Erreur préparation maj adresse: " . mysqli_error($conn));//INDIQUE LE PB
                    }
                } else {//SI IL A PAS D4ADRESSE
                    $sql_insert_address = "INSERT INTO adresse (Adresse, Ville, CodePostal, InfosComplementaires) VALUES (?, ?, ?, ?)";//ON LUI AJOUTE UNE LIGNE DANS LA BDD
                    $stmt_insert_address = mysqli_prepare($conn, $sql_insert_address);
                    if ($stmt_insert_address) {
                        mysqli_stmt_bind_param($stmt_insert_address, "ssss", $adresse_ligne, $ville, $code_postal, $infos_complementaires);//ON RELIE A LA DEMANDE LES INFOS
                        if (mysqli_stmt_execute($stmt_insert_address)) {
                            $new_adresse_id = mysqli_insert_id($conn);
                            /// Lier le nouvel ID d'adresse à l'utilisateur
                            if ($type === 'client') {//SI C EST UN CLIENT
                                $sql_link_address = "UPDATE utilisateurs_client SET ID_Adresse = ? WHERE ID = ?";//ON LE RELIE A LA BASE DE DONNE DU CLIENT
                            } else { //SI CEST UN PERSONNEL
                                $sql_link_address = "UPDATE utilisateurs_personnel SET ID_Adresse = ? WHERE ID = ?";//ON LE RELIE A LA BDD DU PERSONNEL
                            }
                            $stmt_link_address = mysqli_prepare($conn, $sql_link_address);
                            if ($stmt_link_address) {
                                mysqli_stmt_bind_param($stmt_link_address, "ii", $new_adresse_id, $user_id);//ON VA RELIER L ID DE L UTILISATEUR ET CELLE DE L ADRESSE
                                if (!mysqli_stmt_execute($stmt_link_address)) {//ON VERIFIE ICI SI TOUT EST OK
                                    $all_queries_success = false;//SI CA ECHOUE
                                    error_log("Échec liaison adresse: " . mysqli_stmt_error($stmt_link_address));//ON ANNONCE QU UIL Y A UN PB
                                }
                                mysqli_stmt_close($stmt_link_address);//ON REFERME
                            } else {
                                $all_queries_success = false;//SI CA MARCHE PAS
                                error_log("Erreur " . mysqli_error($conn));//ON INDIQUE A LA PERSONNE QU IL Y A UNE ERREUR
                            }
                        } else {
                            $all_queries_success = false;//SI CA  APAS MARCHE
                            error_log("Échec insertion adresse: " . mysqli_stmt_error($stmt_insert_address));//ON INDIQUE QU IL Y A UNE ERREUR
                        }
                        mysqli_stmt_close($stmt_insert_address);///ON REFERME
                    } else {
                        $all_queries_success = false;//SI CA A PAS MARCHE
                        error_log("Erreur préparation insertion adresse: " . mysqli_error($conn));//ON INDIQUE LE PB
                    }
                }
            }

            if ($all_queries_success) {
                if ($type === 'client') {//SI C EST UN CLIENT
                    $sql_update_client = "UPDATE utilisateurs_client SET Telephone = ?, CarteVitale = ? WHERE ID = ?";//ON MET A JOUR SES INFO
                    $stmt_update_client = mysqli_prepare($conn, $sql_update_client);//ON MET EN PLACE POUR CHANGEMENT
                    if ($stmt_update_personnel) {
                    if ($stmt_update_client) {
                        mysqli_stmt_bind_param($stmt_update_client, "ssi", $telephone, $carte_vitale, $user_id);//ON MET A JOUR
                        if (!mysqli_stmt_execute($stmt_update_client)) {//SI ON ARRIVE PAS A EXECUTER LA DEMANDE
                            $all_queries_success = false;
                            error_log("Échec maj client: " . mysqli_stmt_error($stmt_update_client));//ON PREVIENT QUE CA N A PAS PU SE FAIRE
                        }
                        mysqli_stmt_close($stmt_update_client);//ON FERME
                    } else {
                        $all_queries_success = false;
                        error_log("Erreur préparation maj client: " . mysqli_error($conn));
                    }
                } elseif ($type === 'personnel') {///SI C EST UN PERSONNEL
                    $sql_update_personnel = "UPDATE utilisateurs_personnel SET Telephone = ?, Description = ?, Type = ? WHERE ID = ?";//ON VA METTRE A NIVEAU CES INFOS LA
                    $stmt_update_personnel = mysqli_prepare($conn, $sql_update_personnel);//ON MET EN PLACE POUR CHANGEMENT
                    if ($stmt_update_personnel) {
                        mysqli_stmt_bind_param($stmt_update_personnel, "sssi", $telephone, $description, $specialite, $user_id);//LES VARIABLES QUI SONT A MODIF
                        if (!mysqli_stmt_execute($stmt_update_personnel)) {//SI CA NE MARCHE PAS
                            $all_queries_success = false;
                            error_log("Échec maj personnel: " . mysqli_stmt_error($stmt_update_personnel));//ON PREVIENT QUE C A MARCHE PAS
                        }
                        mysqli_stmt_close($stmt_update_personnel);//ON FERMER
                    } else {
                        $all_queries_success = false;
                        error_log("Erreur" . mysqli_error($conn));//ON PREVIENT QEU PAS POSSOBLE
                    }
                }
            }

            if ($all_queries_success) {// SI LES MODIFS ONT ETE VALIDE ET QU ON PEUT LES GARDER
                mysqli_commit($conn);//ON SE RELIE A LA BASE DE DONNEE
                $_SESSION['profile_success'] = "Votre profil a été mis à jour avec succès.";//ON INDIQUE A L UTILISATEUR QUE CA  A BIEN ETE MODIFIE
                mysqli_close($conn);
                header("Location: profil.php");//ON RETOURNE SUR NOTRE PAGE DE PROFIL AVEC LES NOUVELLES INFOS
                exit();
            } else {//SINON SI CA A PAS MARCHE
                mysqli_rollback($conn);//ON ANNULE LES INFOS QU ON A COMMENCE A METTRE DANS LA BASE DE DONNE
                $_SESSION['profile_error'] = "Erreur lors de la mise à jour de votre profil. Veuillez réessayer.";//ON DIT A L UTILISATEUR QUE YA UN PB ET QU ON NE PEUT PAS GARDER
                $user_data = array_merge($user_data, $_POST);//ON GARDE LES INFOS QUI ETAIENT QUAND MEME BONNE
                $user_data['AdresseLigne'] = $_POST['adresse_ligne'] ?? $user_data['AdresseLigne'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
                $user_data['Ville'] = $_POST['ville'] ?? $user_data['Ville'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
                $user_data['CodePostal'] = $_POST['code_postal'] ?? $user_data['CodePostal'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
                $user_data['InfosComplementaires'] = $_POST['infos_complementaires'] ?? $user_data['InfosComplementaires'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
            }

        } else {
            $user_data = array_merge($user_data, $_POST);//SINON SI Y A UN PB ON REVIENT EN ARRIERE
            $user_data['AdresseLigne'] = $_POST['adresse_ligne'] ?? $user_data['AdresseLigne'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
            $user_data['Ville'] = $_POST['ville'] ?? $user_data['Ville'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
            $user_data['CodePostal'] = $_POST['code_postal'] ?? $user_data['CodePostal'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
            $user_data['InfosComplementaires'] = $_POST['infos_complementaires'] ?? $user_data['InfosComplementaires'];//PERMET DE GARDER LES NOUVELLES SI ELLES SONT BONNES
            $profile_error_message = "Veuillez corriger les erreurs dans le formulaire.";//INDIQUER A UTILISATEUR QU Y A UN PB 
        }
    }

    if (isset($_SESSION['profile_error'])) {//SI IL Y AUN MESS D ERREUR
        $profile_error_message = $_SESSION['profile_error'];//ON MARQUE L ERREUR
        unset($_SESSION['profile_error']);//ON L ENLEVE
    }
    if (isset($_SESSION['profile_success'])) {// SI CA A MARCHE
        $profile_success_message = $_SESSION['profile_success'];//ON MARQUE LA REUSSITE
        unset($_SESSION['profile_success']);//ON AJOUTE 
    }
    }
?>

<!DOCTYPE html> 
<html lang="fr">
    <?php require 'includes/head.php'; ?>

    <body>

        <?php require 'includes/header.php'; ?><!--on garde haut  de page-->

        <main class="profile-main">
            <div class="profile-container">
                <h2 class="profile-title">Modifier Mon Profil</h2>

                <?php if (!empty($profile_error_message)) : ?><!--SII Y A DES ERREURS -->
                    <div class="profile-alert error">
                        <p><?php echo safe_html($profile_error_message); ?></p><!--AFFICHER MESSAGE D ERREUR-->
                    </div>
                <?php endif; ?>
                <?php if (!empty($profile_success_message)) : ?><!--SII CA A MARCHE -->
                    <div class="profile-alert success"><!--ON VA LMETTRE UN MESSAGE POUR DIRE QUE C EST BON -->
                        <p><?php echo safe_html($profile_success_message); ?></p><!--source pour eviter les injection:https://www.php.net/manual/fr/function.htmlspecialchars.php -->
                    </div>
                <?php endif; ?>


                <?php if (!empty($form_errors)): ?><!--SII Y A DES ERREURS -->
                    <div class="profile-alert error">
                        <p>Veuillez corriger les erreurs suivantes :</p><!--TITRE PAGE POUR LES ERREURS -->
                        <ul>
                            <?php foreach ($form_errors as $error): ?><!--POUR CHAUQE ERREUR-->
                                <li><?= safe_html($error) ?></li><!--ON STOCK LE DIFFETRENTES ERREURS -->
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="modifier_profil.php" method="POST" class="profile-edit-form"> <!--PREPARATION DE LA PAGE DE MODIFICATION-->
                    
                    <section class="profile-section"> <!--PREMIERE PARTIE DE LA PAGEE -->
                        <h3 class="section-title">Informations Générales</h3> <!-- INDIQUER AVEC TITRE-->
                        <div class="form-group">
                            <label for="nom">Nom :</label> <!-- PREMIERE INDICATION LE NOM DE LA PERSONNES -->
                            <input type="text" id="nom" name="nom" value="<?= safe_html($user_data['Nom'] ?? '') ?>" required> <!-- INFOR OBLIGATOIRE ET PREND LE NOM ENREGISTRE-->
                            <?php if (isset($form_errors['nom'])) echo "<p class='error-message'>".safe_html($form_errors['nom'])."</p>"; ?><!-- SI Y A UN PB ON L INDIQUE A L AP UTILISATUER-->
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom :</label><!--ON FAIT EXACTEMENT LA MEME CHOSE POUR LE PRENOM DE LA PERSONNE-->
                            <input type="text" id="prenom" name="prenom" value="<?= safe_html($user_data['Prenom'] ?? '') ?>" required>
                            <?php if (isset($form_errors['prenom'])) echo "<p class='error-message'>".safe_html($form_errors['prenom'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="email">Email :</label><!--ET ENCORE PAREILPOUR LE MAIL CETTE FOIS-->
                            <input type="email" id="email" name="email" value="<?= safe_html($user_data['Email'] ?? '') ?>" required>
                            <?php if (isset($form_errors['email'])) echo "<p class='error-message'>".safe_html($form_errors['email'])."</p>"; ?>
                        </div>
                    </section>

                    <section class="profile-section"><!--ON CHZNGE ICI DE SECTION DONC DE PETIT BLOC DANS LA PAGE-->
                        <h3 class="section-title">Adresse</h3><!-- ON INDIQUE QEU C EST POUR LES INFOS SUR L ADRESSE-->
                        <div class="form-group">
                            <label for="adresse_ligne">Adresse:</label><!--ICI ON DIT LA RUE ET LE NUMERO-->
                            <input type="text" id="adresse_ligne" name="adresse_ligne" value="<?= safe_html($user_data['AdresseLigne'] ?? '') ?>" required><!-- ON RECUP L ADRESSE QUI A ETE ENREGISTRER-->
                            <?php if (isset($form_errors['adresse_ligne'])) echo "<p class='error-message'>".safe_html($form_errors['adresse_ligne'])."</p>"; ?><!--SI UN PB ON INDIQUE A LA PERSONNE-->
                        </div>
                        <div class="form-group">
                            <label for="ville">Ville :</label><!--ON FAIT PAREIL AVEC LA VILLE-->
                            <input type="text" id="ville" name="ville" value="<?= safe_html($user_data['Ville'] ?? '') ?>" required>
                            <?php if (isset($form_errors['ville'])) echo "<p class='error-message'>".safe_html($form_errors['ville'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="code_postal">Code Postal :</label><!-- IDEM POUR LE CODE POSTAL-->
                            <input type="text" id="code_postal" name="code_postal" value="<?= safe_html($user_data['CodePostal'] ?? '') ?>" required maxlength="5" pattern="\d{5}" title="Cinq chiffres requis.">
                            <?php if (isset($form_errors['code_postal'])) echo "<p class='error-message'>".safe_html($form_errors['code_postal'])."</p>"; ?>
                        </div>
                        <div class="form-group">
                            <label for="infos_complementaires">Infos Complémentaires :</label><!--ET SI IL A DES INFOS EN PLUS A METTRE SUR SON PROFIL POUR ADRESSSE-->
                            <textarea id="infos_complementaires" name="infos_complementaires"><?= safe_html($user_data['InfosComplementaires'] ?? '') ?></textarea>
                        </div>
                    </section>

                    <?php if ($type === 'client'): ?><!--si c'est un clienr-->
                        <section class="profile-section"><!--premier tableau-->
                            <h3 class="section-title">Informations Client</h3><!--titre de la partie-->
                            <div class="form-group">
                                <label for="telephone">Téléphone :</label><!--donne son numero de tel-->
                                <input type="tel" id="telephone" name="telephone" value="<?= safe_html($user_data['ClientTelephone'] ?? '') ?>" required pattern="^0[67]\d{8}$" title="Numéro de téléphone invalide (Ex: 0612345678)."><!--specif du tle sinon marche pas-->
                                <?php if (isset($form_errors['telephone'])) echo "<p class='error-message'>".safe_html($form_errors['telephone'])."</p>"; ?><!--indiquer a l utilklsateur-->
                            </div>
                            <div class="form-group">
                                <label for="carte_vitale">Carte Vitale :</label>
                                <input type="text" id="carte_vitale" name="carte_vitale" value="<?= safe_html($user_data['CarteVitale'] ?? '') ?>" required pattern="\d{13,15}" title="Numéro de carte vitale invalide (13 à 15 chiffres).">
                                <?php if (isset($form_errors['carte_vitale'])) echo "<p class='error-message'>".safe_html($form_errors['carte_vitale'])."</p>"; ?>
                            </div>
                        </section>
                    <?php elseif ($type === 'personnel'): ?><!--si c est un personnel-->
                        <section class="profile-section">
                            <h3 class="section-title">Informations Professionnelles</h3><!--otitre de la partie -->
                            <div class="form-group">
                                <label for="telephone">Téléphone :</label><!--sous partie pour dire numero de tel-->
                                <input type="tel" id="telephone" name="telephone" value="<?= safe_html($user_data['PersonnelTelephone'] ?? '') ?>" required pattern="^0[67]\d{8}$" title="Numéro de téléphone invalide (Ex: 0612345678)."><!--specificite a respecte sinon invalide-->
                                <?php if (isset($form_errors['telephone'])) echo "<p class='error-message'>".safe_html($form_errors['telephone'])."</p>"; ?>
                            </div>
                            <div class="form-group">
                                <label for="specialite">Spécialité :</label><!--sous partie pour dire la specialite-->
                                <input type="text" id="specialite" name="specialite" value="<?= safe_html($user_data['Specialite'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="description">Description :</label><!--sous partie pour ecrire la description-->
                                <textarea id="description" name="description"><?= safe_html($user_data['Description'] ?? '') ?></textarea>
                            </div>
                            <p style="font-size:0.9em; color:#6c757d; margin-top:1.5rem;">Pour modifier votre photo ou vidéo, veuillez contacter l'administration.</p><!--dire a utilisateur que il ne peut pas le fzire-->
                        </section>
                    <?php endif; ?>

                    <div class="profile-actions"><!--derniere partie-->
                        <button type="submit" class="btn-profile-action btn-save-profile">Enregistrer les modifications</button><!--bouton pour pouvoir enregistyre ce qu'on a modifie-->
                        <a href="profil.php" class="btn-profile-action btn-cancel-edit">Annuler</a><!--ou alors annuler ce qu on a fait-->
                    </div>
                </form>
            </div>
        </main>
        <?php require 'includes/footer.php'; ?> <!--on garde bas de page-->

        <?php
            if (isset($conn) && $conn) {
                mysqli_close($conn);//ON FERME
            }
        ?>
    </body>
</html>

<style>
.profile-main {
    padding: 2rem;
    background-color: #f2f2f2;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: calc(100vh - 160px); 
}

.profile-container {
    max-width: 750px;
    width: 100%;
    margin: auto;
    background: #fff;
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    box-sizing: border-box;
}

.profile-title {
    text-align: center;
    color: #0a7abf;
    margin-bottom: 2.5rem;
    font-size: 2.2rem;
    font-weight: 600;
}

.profile-section {
    background-color: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.section-title {
    color: #0a7abf;
    font-size: 1.4rem;
    margin-top: 0;
    margin-bottom: 1.5rem;
    padding-bottom: 0.8rem;
    border-bottom: 2px solid #eaf5ff;
}

.profile-edit-form .form-group {
    margin-bottom: 1rem;
}

.profile-edit-form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.profile-edit-form input[type="text"],
.profile-edit-form input[type="email"],
.profile-edit-form input[type="tel"],
.profile-edit-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
    box-sizing: border-box; 
    transition: border-color 0.2s ease;
}

.profile-edit-form input:focus,
.profile-edit-form textarea:focus {
    outline: none;
    border-color: #0a7abf;
    box-shadow: 0 0 0 3px rgba(10, 122, 191, 0.2);
}

.profile-edit-form textarea {
    min-height: 80px;
    resize: vertical;
}

.profile-actions {
    text-align: center;
    margin-top: 3rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
}

.btn-profile-action {
    display: inline-block;
    padding: 12px 25px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    transition: background-color 0.2s ease, transform 0.2s ease;
    min-width: 180px;
    text-align: center;
    border: none; 
    cursor: pointer;
}

.btn-save-profile {
    background-color: #28a745; 
}
.btn-save-profile:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.btn-cancel-edit {
    background-color: #6c757d; 
}
.btn-cancel-edit:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

.error-message {
    color: #dc3545;
    font-size: 0.85em;
    margin-top: 5px;
}

.profile-alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-weight: 500;
}
.profile-alert.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    text-align: center;
}
.profile-alert.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.profile-alert.error ul {
    margin-top: 10px;
    margin-bottom: 0;
    padding-left: 20px;
    text-align: left;
}
.profile-alert.error ul li {
    margin-bottom: 5px;
}
</style>