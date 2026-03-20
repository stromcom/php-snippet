<?php
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
  ->in([
    __DIR__ . '/src',
    __DIR__ . '/tests',
  ])
  ->name('*.php');

return (new Config())
  ->setFinder($finder)
  ->setRiskyAllowed(true)
  ->setIndent('  ')
  ->setLineEnding("\n")
  ->setRules([
    // ── Ruleset ─────────────────────────────────────────
    '@PER-CS2.0' => true,

    // ── Space & Indention ──────────────────────────────────────
    'indentation_type' => true,

    // ── Empty lines between methods ────────────────────────────
    'class_attributes_separation' => false,

    // ── Trailing newline ───────────────────────
    'single_blank_line_at_eof' => true,

    // ── Importy ────────────────────────────────────────────────
    'ordered_imports' => [
      'sort_algorithm' => 'alpha',
      'imports_order' => ['class', 'function', 'const'],
    ],
    'no_unused_imports' => true,

    // ── Strict types ───────────────────────────────────────────
    'declare_strict_types' => true,
    'strict_param' => true,

    // ── Syntax ─────────────────────────────────────────────────
    'array_syntax' => ['syntax' => 'short'],
    'trailing_comma_in_multiline' => [
      'elements' => ['arguments', 'arrays', 'match', 'parameters'],
    ],

    // ── Blank lines ────────────────────────────────────────────
    'blank_line_after_opening_tag' => false,
    'blank_line_after_namespace' => true,
    'no_extra_blank_lines' => [
      'tokens' => [
        'extra',
        'use',
        'parenthesis_brace_block',
        'return',
        'throw',
      ],
    ],

    // ── Braces ─────────────────────────────────────────────────
    'braces_position' => [
      'classes_opening_brace' => 'same_line',
      'functions_opening_brace' => 'same_line',
      'control_structures_opening_brace' => 'same_line',
    ],
    'single_line_empty_body' => false,
    'no_blank_lines_after_class_opening' => false,

    // ── Spaces ─────────────────────────────────────────────────
    'concat_space' => ['spacing' => 'one'],

    // ── Visibility ─────────────────────────────────────────────
    'visibility_required' => [
      'elements' => ['property', 'method', 'const'],
    ],

    // ── Nullable types ─────────────────────────────────────────
    'nullable_type_declaration_for_default_null_value' => true,

    // ── PHPDoc ─────────────────────────────────────────────────
    'no_empty_phpdoc' => true,
    'phpdoc_trim' => true,
    'phpdoc_align' => ['align' => 'vertical'],
    'phpdoc_separation' => true,
    'phpdoc_scalar' => true,
  ]);
