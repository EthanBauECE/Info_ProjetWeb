<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'error' => ''];

if (!isset($_SESSION["user_id"])) {
    $response['error'] = 'Non authentifié.';
    echo json_encode($response);
    exit();
}

$current_user_id = $_SESSION["user_id"];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=base_donne_web;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_messages':
            $conv_id = intval($_GET['conv_id'] ?? 0);
            if ($conv_id === 0) {
                $response['error'] = 'ID de conversation manquant.';
                break;
            }

            // Vérifier que l'utilisateur fait bien partie de cette conversation
            $stmt_verify_conv = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE ID = ?");
            $stmt_verify_conv->execute([$conv_id]);
            $conv_check = $stmt_verify_conv->fetch();

            if (!$conv_check || ($conv_check['user1_id'] != $current_user_id && $conv_check['user2_id'] != $current_user_id)) {
                $response['error'] = 'Non autorisé à voir cette conversation.';
                break;
            }

            // Récupérer les messages
            $stmt_messages = $pdo->prepare("SELECT sender_id, message_text, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC");
            $stmt_messages->execute([$conv_id]);
            $response['messages'] = $stmt_messages->fetchAll();
            $response['success'] = true;

            // Marquer les messages comme lus pour l'utilisateur actuel
            if ($conv_check['user1_id'] == $current_user_id) {
                $stmt_mark_read = $pdo->prepare("UPDATE conversations SET user1_unread_count = 0 WHERE ID = ?");
            } else {
                $stmt_mark_read = $pdo->prepare("UPDATE conversations SET user2_unread_count = 0 WHERE ID = ?");
            }
            $stmt_mark_read->execute([$conv_id]);

            break;

        case 'get_unread_count':
            $total_unread = 0;
            // Requête pour les messages non lus où l'utilisateur est user1
            $stmt_unread_user1 = $pdo->prepare("SELECT SUM(user1_unread_count) FROM conversations WHERE user1_id = ?");
            $stmt_unread_user1->execute([$current_user_id]);
            $count1 = (int)$stmt_unread_user1->fetchColumn();

            // Requête pour les messages non lus où l'utilisateur est user2
            $stmt_unread_user2 = $pdo->prepare("SELECT SUM(user2_unread_count) FROM conversations WHERE user2_id = ?");
            $stmt_unread_user2->execute([$current_user_id]);
            $count2 = (int)$stmt_unread_user2->fetchColumn();

            $total_unread = $count1 + $count2;
            $response['unread_count'] = $total_unread;
            $response['success'] = true;
            break;

        default:
            $response['error'] = 'Action invalide.';
            break;
    }

} catch (PDOException $e) {
    $response['error'] = 'Erreur BDD: ' . $e->getMessage();
    error_log("Chat API Error: " . $e->getMessage());
} catch (Exception $e) {
    $response['error'] = 'Erreur: ' . $e->getMessage();
    error_log("Chat API Error: " . $e->getMessage());
}

echo json_encode($response);
?>