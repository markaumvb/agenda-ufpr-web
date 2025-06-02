<?php
/**
 * Teste rápido para verificar se a busca funciona
 * Coloque na raiz: https://200.238.174.7/agenda_ufpr/quick_test.php
 */

require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/app/models/Database.php';
require_once __DIR__ . '/app/models/Agenda.php';

echo "<h1>Teste Rápido de Busca</h1>";
echo "<hr>";

try {
    $agendaModel = new Agenda();
    
    echo "<h2>Teste 1: Buscar por 'Age' (deveria encontrar 'Agenda teste Fábio')</h2>";
    $results = $agendaModel->searchPublicAgendas('Age', 1, 10);
    echo "Resultados encontrados: " . count($results) . "<br>";
    
    if (count($results) > 0) {
        echo "✅ SUCESSO! Agendas encontradas:<br>";
        foreach ($results as $agenda) {
            echo "- " . htmlspecialchars($agenda['title']) . " (ID: " . $agenda['id'] . ")<br>";
        }
    } else {
        echo "❌ Nenhum resultado encontrado<br>";
    }
    
    echo "<hr>";
    
    echo "<h2>Teste 2: Buscar por 'teste' (case-insensitive)</h2>";
    $results2 = $agendaModel->searchPublicAgendas('teste', 1, 10);
    echo "Resultados encontrados: " . count($results2) . "<br>";
    
    if (count($results2) > 0) {
        echo "✅ SUCESSO! Agendas encontradas:<br>";
        foreach ($results2 as $agenda) {
            echo "- " . htmlspecialchars($agenda['title']) . " (ID: " . $agenda['id'] . ")<br>";
        }
    } else {
        echo "❌ Nenhum resultado encontrado<br>";
    }
    
    echo "<hr>";
    
    echo "<h2>Teste 3: Buscar por 'Lab' (deveria encontrar laboratórios)</h2>";
    $results3 = $agendaModel->searchPublicAgendas('Lab', 1, 10);
    echo "Resultados encontrados: " . count($results3) . "<br>";
    
    if (count($results3) > 0) {
        echo "✅ SUCESSO! Agendas encontradas:<br>";
        foreach ($results3 as $agenda) {
            echo "- " . htmlspecialchars($agenda['title']) . " (ID: " . $agenda['id'] . ")<br>";
        }
    } else {
        echo "❌ Nenhum resultado encontrado<br>";
    }
    
    echo "<hr>";
    
    echo "<h2>Teste 4: Contagem de resultados</h2>";
    $count = $agendaModel->countPublicAgendasWithSearch('Age');
    echo "Contagem para 'Age': $count<br>";
    
    $count2 = $agendaModel->countPublicAgendasWithSearch('Lab');
    echo "Contagem para 'Lab': $count2<br>";
    
    echo "<hr>";
    
    echo "<h2>Resumo dos Testes</h2>";
    if (count($results) > 0 && count($results2) > 0 && count($results3) > 0) {
        echo "🎉 <strong>TODOS OS TESTES PASSARAM!</strong><br>";
        echo "✅ A busca está funcionando corretamente<br>";
        echo "✅ Case-insensitive funcionando<br>";
        echo "✅ Busca por termos parciais funcionando<br>";
        echo "<br>";
        echo "🚀 <strong>Você pode agora testar na página inicial:</strong><br>";
        echo "<a href='" . BASE_URL . "/' target='_blank' style='font-size: 18px; color: #004a8f;'>" . BASE_URL . "/</a>";
    } else {
        echo "❌ Alguns testes falharam. Verifique o arquivo Agenda.php<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='" . BASE_URL . "/' target='_blank'>← Testar na página inicial</a></p>";
?>