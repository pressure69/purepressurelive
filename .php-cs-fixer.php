<?php
$rules = [
  '@PSR12' => true,
  'array_syntax' => ['syntax' => 'short'],
  'ordered_imports' => true,
  'no_unused_imports' => true,
  'single_quote' => true,
];
return (new PhpCsFixer\Config())
  ->setRiskyAllowed(true)
  ->setRules($rules)
  ->setFinder(PhpCsFixer\Finder::create()->in([__DIR__.'/src', __DIR__]));
