<?php /////////////////////////////////////////////// PHP //////////////////////////////////////////

    // ______________/ Configuration API Messagerie \_____________________

    /// Démarrage de la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /// Configuration de l'entête et des erreurs
    header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Mettre à 0 en production

    /// Réponse par défaut
    $response = ['success' => false, 'error' => ''];


    // ______________/ Vérification de l'Authentification \_____________________

    /// Si l'utilisateur n'est pas connecté
    if (!isset($_SESSION["user_id"])) {
        $response['error'] = 'Non authentifié.';
        echo json_encode($response);
        exit();
    }

    $current_user_id = $_SESSION["user_id"];


    // ______________/ Connexion à la Base de Données \_____________________
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'base_donne_web';

    /// On se connecte a la base de donne
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    /// Vérification de la connexion MySQLi
    if (!$conn) {
        $response['error'] = "Erreur de connexion BDD: " . mysqli_connect_error();
        error_log("Chat API DB Connection Error: " . mysqli_connect_error());
        echo json_encode($response);
        exit();
    }
    mysqli_set_charset($conn, 'utf8');

    /// Activation des rapports d'erreurs MySQLi pour lever des exceptions (similaire à PDO::ERRMODE_EXCEPTION)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


    // ______________/ Traitement des Actions \_____________________
    try {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            // ______________/ Récupérer les Messages d'une Conversation \_____________________
            case 'get_messages':
                $conv_id = intval($_GET['conv_id'] ?? 0);

                /// Vérification de l'ID de conversation
                if ($conv_id === 0) {
                    $response['error'] = 'ID de conversation manquant.';
                    break;
                }

                /// Vérifier que l'utilisateur fait bien partie de cette conversation
                $sql_verify_conv = "SELECT user1_id, user2_id FROM conversations WHERE ID = ?";
                $stmt_verify_conv = mysqli_prepare($conn, $sql_verify_conv);
                mysqli_stmt_bind_param($stmt_verify_conv, "i", $conv_id);
                mysqli_stmt_execute($stmt_verify_conv);
                $result_verify_conv = mysqli_stmt_get_result($stmt_verify_conv);
                $conv_check = mysqli_fetch_assoc($result_verify_conv);
                mysqli_free_result($result_verify_conv);
                mysqli_stmt_close($stmt_verify_conv);


                if (!$conv_check || ($conv_check['user1_id'] != $current_user_id && $conv_check['user2_id'] != $current_user_id)) {
                    $response['error'] = 'Non autorisé à voir cette conversation.';
                    break;
                }

                /// Récupérer les messages
                $sql_messages = "SELECT sender_id, message_text, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC";
                $stmt_messages = mysqli_prepare($conn, $sql_messages);
                mysqli_stmt_bind_param($stmt_messages, "i", $conv_id);
                mysqli_stmt_execute($stmt_messages);
                $result_messages = mysqli_stmt_get_result($stmt_messages);
                $response['messages'] = mysqli_fetch_all($result_messages, MYSQLI_ASSOC);
                $response['success'] = true;
                mysqli_free_result($result_messages);
                mysqli_stmt_close($stmt_messages);

                /// Marquer les messages comme lus pour l'utilisateur actuel
                $sql_mark_read = "";
                if ($conv_check['user1_id'] == $current_user_id) {
                    $sql_mark_read = "UPDATE conversations SET user1_unread_count = 0 WHERE ID = ?";
                } else {
                    $sql_mark_read = "UPDATE conversations SET user2_unread_count = 0 WHERE ID = ?";
                }
                $stmt_mark_read = mysqli_prepare($conn, $sql_mark_read);
                mysqli_stmt_bind_param($stmt_mark_read, "i", $conv_id);
                mysqli_stmt_execute($stmt_mark_read);
                mysqli_stmt_close($stmt_mark_read);

                break;

            // ______________/ Récupérer le Nombre de Messages Non Lus \_____________________
            case 'get_unread_count':
                $total_unread = 0;

                /// Requête pour les messages non lus où l'utilisateur est user1
                $sql_unread_user1 = "SELECT SUM(user1_unread_count) as total_unread FROM conversations WHERE user1_id = ?";
                $stmt_unread_user1 = mysqli_prepare($conn, $sql_unread_user1);
                mysqli_stmt_bind_param($stmt_unread_user1, "i", $current_user_id);
                mysqli_stmt_execute($stmt_unread_user1);
                $result_unread_user1 = mysqli_stmt_get_result($stmt_unread_user1);
                $row1 = mysqli_fetch_assoc($result_unread_user1);
                $count1 = (int)($row1['total_unread'] ?? 0);
                mysqli_free_result($result_unread_user1);
                mysqli_stmt_close($stmt_unread_user1);


                /// Requête pour les messages non lus où l'utilisateur est user2
                $sql_unread_user2 = "SELECT SUM(user2_unread_count) as total_unread FROM conversations WHERE user2_id = ?";
                $stmt_unread_user2 = mysqli_prepare($conn, $sql_unread_user2);
                mysqli_stmt_bind_param($stmt_unread_user2, "i", $current_user_id);
                mysqli_stmt_execute($stmt_unread_user2);
                $result_unread_user2 = mysqli_stmt_get_result($stmt_unread_user2);
                $row2 = mysqli_fetch_assoc($result_unread_user2);
                $count2 = (int)($row2['total_unread'] ?? 0);
                mysqli_free_result($result_unread_user2);
                mysqli_stmt_close($stmt_unread_user2);

                $total_unread = $count1 + $count2;
                $response['unread_count'] = $total_unread;
                $response['success'] = true;
                break;

            // ______________/ Action Invalide \_____________________
            default:
                $response['error'] = 'Action invalide.';
                break;
        }

    } catch (mysqli_sql_exception $e) { /// Erreurs spécifiques à MySQLi
        $response['error'] = 'Erreur BDD: ' . $e->getMessage();
        error_log("Chat API MySQLi Error: " . $e->getMessage() . " (Query: " . (isset($stmt_verify_conv) ? $sql_verify_conv : (isset($stmt_messages) ? $sql_messages : (isset($stmt_mark_read) ? $sql_mark_read : (isset($stmt_unread_user1) ? $sql_unread_user1 : (isset($stmt_unread_user2) ? $sql_unread_user2 : 'N/A'))))) . ")");
    } catch (Exception $e) { /// Autres erreurs
        $response['error'] = 'Erreur: ' . $e->getMessage();
        error_log("Chat API General Error: " . $e->getMessage());
    }


    // ______________/ Fermeture de la Connexion et Réponse \_____________________

    /// On ferme la connexion a la BD
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }

    /// Envoi de la réponse JSON
    echo json_encode($response);
?>