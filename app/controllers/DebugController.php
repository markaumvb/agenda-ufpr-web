<?php
// app/controllers/DebugController.php

require_once __DIR__ . '/../helpers/DebugHelper.php';

class DebugController {
    public function log() {
        // Registrar mensagem de depuração do cliente
        if (isset($_POST['message'])) {
            $data = $_POST;
            unset($data['message']); // Remover a mensagem para exibir separadamente
            
            DebugHelper::log("Client debug: " . $_POST['message'], !empty($data) ? $data : null);
        }
        
        // Responder com sucesso
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}