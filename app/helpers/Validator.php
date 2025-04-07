<?php
// app/helpers/Validator.php

/**
 * Classe para validação de dados de entrada
 */
class Validator {
    private $data;             // Dados a serem validados
    private $rules;            // Regras de validação
    private $errors = [];      // Erros encontrados
    
    /**
     * Construtor
     * 
     * @param array $data Dados a serem validados
     * @param array $rules Regras de validação
     */
    public function __construct($data, $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    /**
     * Executa a validação
     * 
     * @return bool Resultado da validação
     */
    public function validate() {
        foreach ($this->rules as $field => $fieldRules) {
            // Se o campo não existir nos dados, só é erro se for obrigatório
            if (!isset($this->data[$field]) || $this->data[$field] === '') {
                if (strpos($fieldRules, 'required') !== false) {
                    $this->addError($field, 'required', 'Este campo é obrigatório');
                }
                continue;
            }
            
            // Obter o valor do campo
            $value = $this->data[$field];
            
            // Aplicar cada regra de validação
            $rulesArray = explode('|', $fieldRules);
            foreach ($rulesArray as $rule) {
                // Verificar se a regra tem parâmetros
                $ruleParts = explode(':', $rule, 2);
                $ruleName = $ruleParts[0];
                $ruleParams = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];
                
                // Executar validação para cada regra
                $this->validateRule($field, $value, $ruleName, $ruleParams);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Retorna todos os erros de validação
     * 
     * @return array Erros de validação
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Retorna os erros para um campo específico
     * 
     * @param string $field Nome do campo
     * @return array Erros do campo
     */
    public function getFieldErrors($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Retorna o primeiro erro de cada campo
     * 
     * @return array Primeiro erro de cada campo
     */
    public function getFirstErrors() {
        $firstErrors = [];
        foreach ($this->errors as $field => $errors) {
            if (!empty($errors)) {
                $firstErrors[$field] = reset($errors);
            }
        }
        return $firstErrors;
    }
    
    /**
     * Adiciona um erro de validação
     * 
     * @param string $field Nome do campo
     * @param string $rule Regra que falhou
     * @param string $message Mensagem de erro
     */
    private function addError($field, $rule, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][$rule] = $message;
    }
    
    /**
     * Valida uma regra específica
     * 
     * @param string $field Nome do campo
     * @param mixed $value Valor do campo
     * @param string $rule Nome da regra
     * @param array $params Parâmetros da regra
     */
    private function validateRule($field, $value, $rule, $params) {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->addError($field, $rule, 'Este campo é obrigatório');
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, $rule, 'Este campo deve conter um e-mail válido');
                }
                break;
                
            case 'min':
                if (isset($params[0])) {
                    $min = (int)$params[0];
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $this->addError($field, $rule, "Este campo deve ter no mínimo {$min} caracteres");
                    } elseif (is_numeric($value) && $value < $min) {
                        $this->addError($field, $rule, "Este campo deve ser maior ou igual a {$min}");
                    }
                }
                break;
                
            case 'max':
                if (isset($params[0])) {
                    $max = (int)$params[0];
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $this->addError($field, $rule, "Este campo deve ter no máximo {$max} caracteres");
                    } elseif (is_numeric($value) && $value > $max) {
                        $this->addError($field, $rule, "Este campo deve ser menor ou igual a {$max}");
                    }
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, $rule, 'Este campo deve conter apenas números');
                }
                break;
                
            case 'integer':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, $rule, 'Este campo deve conter um número inteiro');
                }
                break;
                
            case 'date':
                $format = $params[0] ?? 'Y-m-d';
                $dateTime = DateTime::createFromFormat($format, $value);
                if (!$dateTime || $dateTime->format($format) !== $value) {
                    $this->addError($field, $rule, 'Este campo deve conter uma data válida');
                }
                break;
                
            case 'datetime':
                $format = $params[0] ?? 'Y-m-d H:i:s';
                $dateTime = DateTime::createFromFormat($format, $value);
                if (!$dateTime || $dateTime->format($format) !== $value) {
                    $this->addError($field, $rule, 'Este campo deve conter uma data e hora válidas');
                }
                break;
                
            case 'in':
                if (!empty($params) && !in_array($value, $params)) {
                    $validValues = implode(', ', $params);
                    $this->addError($field, $rule, "Este campo deve ser um dos seguintes valores: {$validValues}");
                }
                break;
                
            case 'regex':
                if (isset($params[0])) {
                    $pattern = $params[0];
                    if (!preg_match($pattern, $value)) {
                        $this->addError($field, $rule, 'Este campo contém um formato inválido');
                    }
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, $rule, 'Este campo deve conter uma URL válida');
                }
                break;
                
            case 'confirmed':
                $confirmationField = $field . '_confirmation';
                if (!isset($this->data[$confirmationField]) || $value !== $this->data[$confirmationField]) {
                    $this->addError($field, $rule, 'A confirmação deste campo não corresponde');
                }
                break;
                
            case 'alpha':
                if (!preg_match('/^[a-zA-Z]+$/', $value)) {
                    $this->addError($field, $rule, 'Este campo deve conter apenas letras');
                }
                break;
                
            case 'alpha_num':
                if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    $this->addError($field, $rule, 'Este campo deve conter apenas letras e números');
                }
                break;
                
            case 'alpha_dash':
                if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                    $this->addError($field, $rule, 'Este campo deve conter apenas letras, números, traços e sublinhados');
                }
                break;
                
            case 'boolean':
                $validValues = [true, false, 1, 0, '1', '0', 'true', 'false'];
                if (!in_array($value, $validValues, true)) {
                    $this->addError($field, $rule, 'Este campo deve ser um valor booleano');
                }
                break;
                
            case 'date_after':
                if (isset($params[0])) {
                    $date = $params[0];
                    $format = $params[1] ?? 'Y-m-d';
                    
                    $valueDate = DateTime::createFromFormat($format, $value);
                    $afterDate = DateTime::createFromFormat($format, $date);
                    
                    if (!$valueDate || !$afterDate || $valueDate <= $afterDate) {
                        $this->addError($field, $rule, "Este campo deve ser uma data posterior a {$date}");
                    }
                }
                break;
                
            case 'date_before':
                if (isset($params[0])) {
                    $date = $params[0];
                    $format = $params[1] ?? 'Y-m-d';
                    
                    $valueDate = DateTime::createFromFormat($format, $value);
                    $beforeDate = DateTime::createFromFormat($format, $date);
                    
                    if (!$valueDate || !$beforeDate || $valueDate >= $beforeDate) {
                        $this->addError($field, $rule, "Este campo deve ser uma data anterior a {$date}");
                    }
                }
                break;
                
            case 'time_after_field':
                if (isset($params[0]) && isset($this->data[$params[0]])) {
                    $otherField = $params[0];
                    $otherValue = $this->data[$otherField];
                    
                    $format = $params[1] ?? 'Y-m-d H:i:s';
                    
                    $valueTime = DateTime::createFromFormat($format, $value);
                    $otherTime = DateTime::createFromFormat($format, $otherValue);
                    
                    if (!$valueTime || !$otherTime || $valueTime <= $otherTime) {
                        $this->addError($field, $rule, "Este campo deve ser posterior ao campo {$otherField}");
                    }
                }
                break;
        }
    }
    
    /**
     * Cria e executa uma validação
     * 
     * @param array $data Dados a serem validados
     * @param array $rules Regras de validação
     * @return self Instância do validador
     * @throws ValidationException Se a validação falhar
     */
    public static function make($data, $rules) {
        $validator = new self($data, $rules);
        
        if (!$validator->validate()) {
            throw new ValidationException(
                'Validation failed',
                $validator->getErrors(),
                'Por favor, verifique os campos preenchidos.'
            );
        }
        
        return $validator;
    }
    
    /**
     * Filtra os dados com base nas regras
     * 
     * @return array Dados validados e limpos
     */
    public function validated() {
        $validated = [];
        
        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->sanitize($field, $this->data[$field]);
            }
        }
        
        return $validated;
    }
    
    /**
     * Sanitiza um valor com base no campo
     * 
     * @param string $field Nome do campo
     * @param mixed $value Valor do campo
     * @return mixed Valor sanitizado
     */
    private function sanitize($field, $value) {
        // Sanitizar com base no tipo de campo (inferido pelo nome ou regras)
        if (strpos($field, 'email') !== false) {
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        } elseif (strpos($this->rules[$field], 'numeric') !== false || strpos($this->rules[$field], 'integer') !== false) {
            return is_numeric($value) ? $value : 0;
        } elseif (strpos($field, 'html') !== false || strpos($field, 'content') !== false) {
            // Permitir HTML, mas remover scripts e atributos perigosos
            return $this->sanitizeHtml($value);
        } else {
            // Para a maioria dos campos, sanitizar como string
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Sanitiza conteúdo HTML
     * 
     * @param string $html HTML para sanitizar
     * @return string HTML sanitizado
     */
    private function sanitizeHtml($html) {
        // Lista de tags permitidas
        $allowedTags = '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        
        // Remover todas as tags não permitidas
        $html = strip_tags($html, $allowedTags);
        
        // Remover atributos perigosos (on*)
        $html = preg_replace('/(<[^>]+)on\w+\s*=\s*["\'][^"\']*["\']([^>]*>)/i', '$1$2', $html);
        $html = preg_replace('/(<[^>]+)javascript\s*:[^"\']*/i', '$1', $html);
        
        return $html;
    }
}