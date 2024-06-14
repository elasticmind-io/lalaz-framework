<?php

$projectName = $argv[1] ?? 'lalaz-new-project';
$templateRepository = 'seu-usuario/meu-template';

// Comando para criar o projeto usando o Composer
$command = "composer create-project {$templateRepository} {$projectName}";

// Executa o comando no shell
echo "Criando projeto {$projectName} a partir do template {$templateRepository}...\n";
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo "Projeto {$projectName} criado com sucesso!\n";
} else {
    echo "Houve um erro ao criar o projeto.\n";
    print_r($output);
}
