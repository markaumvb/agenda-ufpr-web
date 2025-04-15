<?php
// app/models/Agenda.php

class Agenda {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Retorna todas as agendas de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $search Termo de pesquisa (opcional)
     * @return array Lista de agendas
     */
    public function getAllByUser($userId, $search = null, $includeInactive = false) {
        try {
            $query = "
                SELECT a.*, 
                       (SELECT COUNT(*) FROM compromissos c WHERE c.agenda_id = a.id) as total_compromissos
                FROM agendas a
                WHERE a.user_id = :user_id
            ";
            
            // Adicionar filtro de status se necessário
            if (!$includeInactive) {
                $query .= " AND a.is_active = 1";
            }
            
            // Adiciona filtro de pesquisa se especificado
            if ($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search)";
            }
            
            $query .= " ORDER BY a.title";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($search) {
                $searchParam = "%{$search}%";
                $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o número total de agendas de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $search Termo de busca opcional
     * @return int Total de agendas
     */
    public function countAllByUser($userId, $search = null) {
        try {
            $query = "SELECT COUNT(*) FROM agendas WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            if ($search) {
                $query .= " AND (title LIKE :search OR description LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar agendas: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retorna agendas com paginação
     * 
     * @param int $userId ID do usuário
     * @param int $offset Início da paginação
     * @param int $limit Limite de registros
     * @param string $search Termo de busca opcional
     * @return array Lista de agendas
     */
    public function getAllByUserPaginated($userId, $offset = 0, $limit = 10, $search = null) {
        try {
            $query = "
                SELECT a.*, 
                       (SELECT COUNT(*) FROM compromissos c WHERE c.agenda_id = a.id) as total_compromissos
                FROM agendas a
                WHERE a.user_id = :user_id
            ";
            
            $params = ['user_id' => $userId];
            
            // Adiciona filtro de pesquisa se especificado
            if ($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $query .= " ORDER BY a.title LIMIT :offset, :limit";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                if ($key !== 'offset' && $key !== 'limit') {
                    $stmt->bindValue(':' . $key, $value);
                }
            }
            
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retorna uma agenda específica
     * 
     * @param int $id ID da agenda
     * @return array|false Dados da agenda ou false se não encontrada
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM agendas WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $agenda = $stmt->fetch();
            
            // Garantir que is_active seja tratado como booleano
            if ($agenda) {
                $agenda['is_active'] = (bool)(int)$agenda['is_active'];
            }
            
            return $agenda;
        } catch (PDOException $e) {
            error_log('Erro ao buscar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se uma agenda pertence a um usuário
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool Se a agenda pertence ao usuário
     */
    public function belongsToUser($agendaId, $userId) {
        try {
            $query = "SELECT COUNT(*) FROM agendas WHERE id = :agenda_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar propriedade da agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma nova agenda
     * 
     * @param array $data Dados da agenda
     * @return int|false ID da agenda criada ou false em caso de erro
     */
    public function create($data) {
        try {
            // Verificar se já existe uma agenda com o mesmo título para o usuário
            $existsQuery = "SELECT COUNT(*) FROM agendas WHERE user_id = :user_id AND title = :title";
            $existsStmt = $this->db->prepare($existsQuery);
            $existsStmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $existsStmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $existsStmt->execute();
            
            if ((int)$existsStmt->fetchColumn() > 0) {
                // Já existe uma agenda com esse título
                $data['title'] = $data['title'] . ' (' . date('d/m/Y H:i') . ')';
            }
            
            // Gerar hash público para agendas públicas
            $publicHash = '';
            if ($data['is_public']) {
                $publicHash = md5(uniqid(rand(), true));
            }
            
            // Definir is_active com valor padrão se não for fornecido
            $isActive = isset($data['is_active']) ? $data['is_active'] : 1;
            
            $query = "
                INSERT INTO agendas (user_id, title, description, is_public, color, created_at, public_hash, is_active)
                VALUES (:user_id, :title, :description, :is_public, :color, NOW(), :public_hash, :is_active)
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
            $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
            $stmt->bindParam(':public_hash', $publicHash, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $isActive, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao criar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza uma agenda existente
     * 
     * @param int $id ID da agenda
     * @param array $data Dados a serem atualizados
     * @return bool Resultado da operação
     */
    public function update($id, $data) {
        try {
            // Obter a agenda atual
            $currentAgenda = $this->getById($id);
            if (!$currentAgenda) {
                return false;
            }
            
            // Se estiver mudando de privada para pública, gerar hash público
            $publicHash = $currentAgenda['public_hash'];
            if ($data['is_public'] && empty($publicHash)) {
                $publicHash = md5(uniqid(rand(), true));
            }
            
            $query = "
                UPDATE agendas
                SET title = :title,
                    description = :description,
                    is_public = :is_public,
                    color = :color,
                    public_hash = :public_hash,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
            $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
            $stmt->bindParam(':public_hash', $publicHash, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao atualizar agenda: ' . $e->getMessage());
            return false;
        }
    }

    /** Ativar ou desativar  a agenda */
    public function toggleActive($id, $status) {
        try {
            $query = "UPDATE agendas SET is_active = :is_active, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':is_active', $status, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao alterar status de ativação da agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui uma agenda
     * 
     * @param int $id ID da agenda
     * @return bool Resultado da operação
     */
    public function delete($id) {
        try {
            // Verificar se há compromissos pendentes ou aguardando aprovação
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id 
                AND (status = 'pendente' OR status = 'aguardando_aprovacao')
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ((int)$stmt->fetchColumn() > 0) {
                // Não pode excluir se houver compromissos pendentes
                return false;
            }
            
            // Iniciar transação para garantir consistência
            $this->db->beginTransaction();
            
            // Excluir compartilhamentos
            $query = "DELETE FROM agenda_shares WHERE agenda_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Excluir compromissos
            $query = "DELETE FROM compromissos WHERE agenda_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Excluir a agenda
            $query = "DELETE FROM agendas WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao excluir agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Conta os compromissos de uma agenda por status
     * 
     * @param int $agendaId ID da agenda
     * @return array Contagem de compromissos por status
     */
    public function countCompromissosByStatus($agendaId) {
        try {
            $query = "
                SELECT 
                    SUM(CASE WHEN status = 'realizado' THEN 1 ELSE 0 END) as realizados,
                    SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN status = 'aguardando_aprovacao' THEN 1 ELSE 0 END) as aguardando_aprovacao,
                    COUNT(*) as total
                FROM compromissos
                WHERE agenda_id = :agenda_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            // Se não houver compromissos, retornar zeros
            if (!$result) {
                return [
                    'realizados' => 0,
                    'cancelados' => 0,
                    'pendentes' => 0,
                    'aguardando_aprovacao' => 0,
                    'total' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log('Erro ao contar compromissos: ' . $e->getMessage());
            return [
                'realizados' => 0,
                'cancelados' => 0,
                'pendentes' => 0,
                'aguardando_aprovacao' => 0,
                'total' => 0
            ];
        }
    }
    
    /**
     * Verifica se uma agenda pode ser excluída
     * 
     * @param int $agendaId ID da agenda
     * @return bool Se a agenda pode ser excluída
     */
    public function canBeDeleted($agendaId) {
        try {
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id 
                AND (status = 'pendente' OR status = 'aguardando_aprovacao')
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar se agenda pode ser excluída: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém todas as agendas que o usuário possui acesso (próprias e compartilhadas)
     * 
     * @param int $userId ID do usuário
     * @param string $search Termo de pesquisa (opcional)
     * @return array Lista de agendas
     */
    public function getAllAccessibleByUser($userId, $search = null, $includeInactive = false) {
        try {
            // Verificar se o usuário existe
            if (!$userId) {
                return [];
            }
            
            // Primeira parte da consulta: agendas do próprio usuário
            $query = "
                SELECT 
                    a.*,
                    u.name as owner_name,
                    1 as is_owner,
                    1 as can_edit,
                    'owner' as access_type
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                WHERE a.user_id = :user_id";
                
            // Adicionar filtro de status se necessário
            if (!$includeInactive) {
                $query .= " AND a.is_active = 1";
            }
            
            // Segunda parte: agendas compartilhadas, com tratamento para evitar duplicatas
            // Usamos uma subconsulta para pegar apenas o compartilhamento de maior privilégio
            $query .= "
                UNION
                
                SELECT 
                    a.*,
                    u.name as owner_name,
                    0 as is_owner,
                    s.can_edit,
                    'shared' as access_type
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                JOIN (
                    SELECT agenda_id, user_id, MAX(can_edit) as can_edit
                    FROM agenda_shares
                    WHERE user_id = :shared_user_id
                    GROUP BY agenda_id, user_id
                ) s ON a.id = s.agenda_id
                WHERE a.user_id != :exclude_user_id";
                
            // Adicionar filtro de status se necessário
            if (!$includeInactive) {
                $query .= " AND a.is_active = 1";
            }
            
            // Terceira parte: agendas públicas (que não sejam do próprio usuário e não estejam compartilhadas)
            $query .= "
                UNION
                
                SELECT 
                    a.*,
                    u.name as owner_name,
                    0 as is_owner,
                    0 as can_edit,
                    'public' as access_type
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                WHERE a.is_public = 1 
                AND a.user_id != :public_user_id
                AND NOT EXISTS (
                    SELECT 1 
                    FROM agenda_shares 
                    WHERE agenda_id = a.id 
                    AND user_id = :public_shares_user_id
                )";
                
            // Adicionar filtro de status se necessário
            if (!$includeInactive) {
                $query .= " AND a.is_active = 1";
            }
            
            // Adicionar filtro de pesquisa se especificado
            if ($search) {
                $query = "SELECT * FROM ($query) as result WHERE (title LIKE :search OR description LIKE :search)";
            }
            
            $query .= " ORDER BY is_owner DESC, title ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':shared_user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':exclude_user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':public_user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':public_shares_user_id', $userId, PDO::PARAM_INT);
            
            if ($search) {
                $searchParam = "%{$search}%";
                $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas acessíveis: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o total de agendas que o usuário pode acessar
     * 
     * @param int $userId ID do usuário
     * @param string $search Termo de busca opcional
     * @return int Total de agendas acessíveis
     */
    public function countAllAccessibleByUser($userId, $search = null) {
        try {
            // Verificar se o usuário existe
            if (!$userId) {
                return 0;
            }
            
            // Buscar as agendas do usuário e compartilhadas
            $query = "
                SELECT COUNT(DISTINCT a.id)
                FROM agendas a
                LEFT JOIN agenda_shares s ON a.id = s.agenda_id AND s.user_id = :shared_user_id
                WHERE 
                    a.user_id = :owned_user_id
                    OR s.user_id = :accessed_user_id
                    OR a.is_public = 1
            ";
            
            $params = [
                'shared_user_id' => $userId,
                'owned_user_id' => $userId,
                'accessed_user_id' => $userId
            ];
            
            // Adicionar filtro de pesquisa se especificado
            if ($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar agendas acessíveis: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtém agendas acessíveis pelo usuário com paginação
     * 
     * @param int $userId ID do usuário
     * @param int $offset Início da paginação
     * @param int $limit Limite de registros
     * @param string $search Termo de busca opcional
     * @return array Lista de agendas
     */
    public function getAllAccessibleByUserPaginated($userId, $offset = 0, $limit = 10, $search = null) {
        try {
            // Verificar se o usuário existe
            if (!$userId) {
                return [];
            }
            
            // Buscar as agendas do usuário e compartilhadas
            $query = "
                SELECT 
                    a.*,
                    u.name as owner_name,
                    CASE WHEN a.user_id = :owner_id THEN 1 ELSE 0 END as is_owner,
                    s.can_edit
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN agenda_shares s ON a.id = s.agenda_id AND s.user_id = :shared_user_id
                WHERE 
                    a.user_id = :owned_user_id
                    OR s.user_id = :accessed_user_id
                    OR a.is_public = 1
            ";
            
            $params = [
                'owner_id' => $userId,
                'shared_user_id' => $userId,
                'owned_user_id' => $userId,
                'accessed_user_id' => $userId
            ];
            
            // Adicionar filtro de pesquisa se especificado
            if ($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $query .= " GROUP BY a.id ORDER BY CASE WHEN a.user_id = :sort_user_id THEN 0 ELSE 1 END, a.title";
            $params['sort_user_id'] = $userId;
            
            // Adicionar paginação
            $query .= " LIMIT :offset, :limit";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                if ($key !== 'offset' && $key !== 'limit') {
                    $stmt->bindValue(':' . $key, $value);
                }
            }
            
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas acessíveis: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se um usuário tem acesso à agenda
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool Se o usuário tem acesso
     */
    public function checkUserAccess($agendaId, $userId) {
        try {
            // Verificar se o usuário é o dono da agenda
            if ($this->belongsToUser($agendaId, $userId)) {
                return true;
            }
            
            // Verificar se a agenda é pública
            $agenda = $this->getById($agendaId);
            if ($agenda && $agenda['is_public']) {
                return true;
            }
            
            // Verificar se a agenda foi compartilhada com o usuário
            $query = "SELECT COUNT(*) FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar acesso do usuário à agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um usuário pode editar uma agenda
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool Se o usuário pode editar
     */
    public function canUserEdit($agendaId, $userId) {
        try {
            // Verificar se o usuário é o dono da agenda
            if ($this->belongsToUser($agendaId, $userId)) {
                return true;
            }
            
            // Verificar se a agenda foi compartilhada com o usuário com permissão de edição
            $query = "SELECT can_edit FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            return $result && $result['can_edit'];
        } catch (PDOException $e) {
            error_log('Erro ao verificar permissão de edição do usuário na agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o hash público de uma agenda
     * 
     * @param int $id ID da agenda
     * @param string $hash Novo hash
     * @return bool Resultado da operação
     */
    public function updatePublicHash($id, $hash) {
        try {
            $query = "UPDATE agendas SET public_hash = :hash WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao atualizar hash público: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma agenda pelo hash público
     * 
     * @param string $hash Hash público da agenda
     * @return array|bool Dados da agenda ou false se não encontrada
     */
    public function getByPublicHash($hash) {
        try {
            $query = "SELECT * FROM agendas WHERE public_hash = :hash LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
            $stmt->execute();
            
            $agenda = $stmt->fetch();
            
            // Garantir que is_active e is_public sejam tratados como booleanos
            if ($agenda) {
                $agenda['is_active'] = (bool)(int)$agenda['is_active'];
                $agenda['is_public'] = (bool)(int)$agenda['is_public'];
            }
            
            return $agenda;
        } catch (PDOException $e) {
            error_log('Erro ao buscar agenda por hash: ' . $e->getMessage());
            return false;
        }
    }
}