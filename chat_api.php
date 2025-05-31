<?php 
    if (session_status() === PHP_SESSION_NONE) {//ON VERIFIE QUE C EST LE SEUL PAGE QUI EST LANCEE
        session_start();//SI C EST BON ALORS ON PEUT COMMENCER
    }

    header('Content-Type: application/json');//ON VA ASOOCIER UN TYPE A L APP
    error_reporting(E_ALL);
    ini_set('display_errors', 1); //CE QUI VA PERMETTRE DE STOCKER LES ERREURS
    $response = ['success' => false, 'error' => ''];//ET CA DE POUVOIR LES AFFICHER POUR UTILISATEUR
 
    if (!isset($_SESSION["user_id"])) {//SI UTILISATEUR NE S EST PAS CONNECTE AVANT
        $response['error'] = 'ERREUR';//LE PREVENIR
        echo json_encode($response);
        exit();
    }

    $current_user_id = $_SESSION["user_id"];

    $db_host = 'localhost';//ON SE CONNECTE A LA BASE DE SONNEE
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);//ON SE RELIE A ELLE POUR RECUPERER ET MODIFIER LES INFOS

    if (!$conn) {
        $response['error'] = "Erreur " . mysqli_connect_error();// INFORMEEER DE L ERREUR
        error_log("ERREUR " . mysqli_connect_error());//ERREUR
        echo json_encode($response);
        exit();//ON ARRETE
    }
    mysqli_set_charset($conn, 'utf8');//POUR POPIUVOIR ECRIRERE TOUT CE QU ON VEUT ET AUSSI SYMBOLES
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $action = $_GET['action'] ?? '';//ON RECUP NOS ANCIENS TRUCS

        switch ($action) {
            case 'get_messages'://SI ON VEUT RECUP LES MESS
                $conv_id = intval($_GET['conv_id'] ?? 0);//ON REPREND L ID DE LA CONV POUR UTILISER APRS

                if ($conv_id === 0) { //SI PAS D ID 
                    $response['error'] = 'ID de conversation manquant.';//ON PREVIENT L UTILISATEUR
                    break;
                }

                $sql_verify_conv = "SELECT user1_id, user2_id FROM conversations WHERE ID = ?";//ON REGARDE ICI SI L UTILISATEUR PEUT VOIR LA CONV OU PAS
                $stmt_verify_conv = mysqli_prepare($conn, $sql_verify_conv);
                mysqli_stmt_bind_param($stmt_verify_conv, "i", $conv_id);//ON REGARDE L ID ENCORE UNE FOIS
                mysqli_stmt_execute($stmt_verify_conv);//ON VERIFIE SI CA CORRESPOND BIEN
                $result_verify_conv = mysqli_stmt_get_result($stmt_verify_conv);
                $conv_check = mysqli_fetch_assoc($result_verify_conv);//ON PEUT ICILE RECUPERERE POUR L INSERER
                mysqli_free_result($result_verify_conv);//ON PEUT LAISSER FAIRE
                mysqli_stmt_close($stmt_verify_conv);//ON REFERME


                if (!$conv_check || ($conv_check['user1_id'] != $current_user_id && $conv_check['user2_id'] != $current_user_id)) {//ON VERIFIE QUE LA CONV PEUT SE FAIRE ET QU IL EST AUTRISE
                    $response['error'] = 'Non autorisé à voir cette conversation.';//ANNONCER QU IL A PAS LE DORITE
                    break;//FIN
                }

                $sql_messages = "SELECT sender_id, message_text, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC";//ON RECUP LES ANCIENNES CONV AVEC LES AUTRES
                $stmt_messages = mysqli_prepare($conn, $sql_messages);//MISE A JOUR
                mysqli_stmt_bind_param($stmt_messages, "i", $conv_id);//REGARDER ID DE LA CONV
                mysqli_stmt_execute($stmt_messages);//ON PEUT MTN L EXECUTER
                $result_messages = mysqli_stmt_get_result($stmt_messages);//ON A LE RESULTAT
                $response['messages'] = mysqli_fetch_all($result_messages, MYSQLI_ASSOC);//ON SAUVEGARDE LE SMESS PIOUR PAS LES PERDRE
                $response['success'] = true;//CA A MARCHEE
                mysqli_free_result($result_messages);//EXECUTE RESULTAT
                mysqli_stmt_close($stmt_messages);//ON REFERME

                $sql_mark_read = "";//ON VA INITILAISER OPOUR UTILISER APRES
                if ($conv_check['user1_id'] == $current_user_id) {// SI C EST USER 1 ON MET LE NB DE NON LU A 0
                    $sql_mark_read = "UPDATE conversations SET user1_unread_count = 0 WHERE ID = ?";//ICI ON MET A 0
                } else {
                    $sql_mark_read = "UPDATE conversations SET user2_unread_count = 0 WHERE ID = ?";//SI  CETS LE 2 ON LE META 0
                }
                $stmt_mark_read = mysqli_prepare($conn, $sql_mark_read);//ON PREPARE POUR POUVOIR FAIRE CE QY ON VEUR
                mysqli_stmt_bind_param($stmt_mark_read, "i", $conv_id); //ON VA REGARDER ID POUR UTILISER
                mysqli_stmt_execute($stmt_mark_read);//OJ  FAIT LA MAJ
                mysqli_stmt_close($stmt_mark_read);//OJ REFERME

                break;

            case 'get_unread_count':
                $total_unread = 0;//INIOTIALISATION

                $sql_unread_user1 = "SELECT SUM(user1_unread_count) as total_unread FROM conversations WHERE user1_id = ?";//POUR LES CONV DE LAPREMIER PERSONNE
                $stmt_unread_user1 = mysqli_prepare($conn, $sql_unread_user1);//ON PREPARE POUR LES ACTIONS APRES
                mysqli_stmt_bind_param($stmt_unread_user1, "i", $current_user_id);//REGARDER L ID 
                mysqli_stmt_execute($stmt_unread_user1);//ON EXECUTE CE QU ON VEUT FAIRE
                $result_unread_user1 = mysqli_stmt_get_result($stmt_unread_user1);
                $row1 = mysqli_fetch_assoc($result_unread_user1);
                $count1 = (int)($row1['total_unread'] ?? 0);//ON LUI ATTRIBUT 0 OBLIGATOIRE
                mysqli_free_result($result_unread_user1);// JE LIBERE PIUR PAS DE BUG
                mysqli_stmt_close($stmt_unread_user1);//ON REFERMEE

                $sql_unread_user2 = "SELECT SUM(user2_unread_count) as total_unread FROM conversations WHERE user2_id = ?";//POUR LES CONV DE LA DEUXIEME PERSONNE
                $stmt_unread_user2 = mysqli_prepare($conn, $sql_unread_user2);//ON PREPAPRE POUR ACTIONS PARES
                mysqli_stmt_bind_param($stmt_unread_user2, "i", $current_user_id);//REGARDER ID
                mysqli_stmt_execute($stmt_unread_user2);//ON EXECUTE CE QU ON VEUT FAIRE
                $result_unread_user2 = mysqli_stmt_get_result($stmt_unread_user2);
                $row2 = mysqli_fetch_assoc($result_unread_user2);
                $count2 = (int)($row2['total_unread'] ?? 0);//ON INIT A 0 POUR ENLEVER LES PB
                mysqli_free_result($result_unread_user2);//ON LIBERE POUR EVITER LES BUGS
                mysqli_stmt_close($stmt_unread_user2);//ON FERME 

                $total_unread = $count1 + $count2;// ON VA METTRE ENSEMBLE LES 2 POUR FUSIONNER
                $response['unread_count'] = $total_unread;
                $response['success'] = true;//ON A REUSSI SUPER
                break;
            default://SINON
                $response['error'] = 'Action invalide.';//ON MARQUE QU IL Y A UN PB POUR UTILISTERU
                break;
        }

    } catch (mysqli_sql_exception $e) { 
        $response['error'] = 'Erreur BDD: ' . $e->getMessage();//MESSGE ERREUR POUR INFORMER L UTILISATEUR
        error_log("Chat API MySQLi Error: " . $e->getMessage() . " (Query: " . (isset($stmt_verify_conv) ? $sql_verify_conv : (isset($stmt_messages) ? $sql_messages : (isset($stmt_mark_read) ? $sql_mark_read : (isset($stmt_unread_user1) ? $sql_unread_user1 : (isset($stmt_unread_user2) ? $sql_unread_user2 : 'N/A'))))) . ")");//ON GARDE CE QU I  A PAS MARFCHEB QUAND MEME
    } catch (Exception $e) { //POUR LES AUTRS PB ON INFORME AUSSI
        $response['error'] = 'Erreur: ' . $e->getMessage();//AVEC MESSAGE ERREUR
        error_log("Chat API General Error: " . $e->getMessage());
    }

    if (isset($conn) && $conn) {//ON SE DECONNECTE DE LA BASE DE DONNEE POUR LIBERER
        mysqli_close($conn);//ON REFERME
    }

    echo json_encode($response);
?>