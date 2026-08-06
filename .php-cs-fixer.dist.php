<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(["var", "vendor", "frankenphp"])
    ->notPath(["config/reference.php"])
;

return (new PhpCsFixer\Config())
    ->setRules([
        "@PSR12" => true,
        "@Symfony" => true,
        "declare_strict_types" => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
