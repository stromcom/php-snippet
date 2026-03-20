<?php
declare(strict_types=1);

namespace Stromcom\Snippet\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stromcom\Snippet\Exception\CodeHasherException;
use Stromcom\Snippet\Exception\ConfGenerationException;
use Stromcom\Snippet\Exception\EnvironmentException;
use Stromcom\Snippet\Exception\GenerationException;
use Stromcom\Snippet\Exception\HomeGenerationException;
use Stromcom\Snippet\Exception\JsonEncodingException;
use Stromcom\Snippet\Exception\OptionsException;
use Stromcom\Snippet\Exception\SerializationException;
use Stromcom\Snippet\Exception\SnippetGenerationException;
use Stromcom\Snippet\Exception\ThreadGenerationException;
use Stromcom\Snippet\Exception\UserGenerationException;
use Stromcom\Snippet\SnippetException;

class ExceptionHierarchyTest extends TestCase {

  #[Test]
  public function all_exception_layers_extend_snippet_exception(): void {
    $this->assertInstanceOf(SnippetException::class, new GenerationException());
    $this->assertInstanceOf(SnippetException::class, new SerializationException());
    $this->assertInstanceOf(SnippetException::class, new OptionsException());
    $this->assertInstanceOf(SnippetException::class, new EnvironmentException());
    $this->assertInstanceOf(SnippetException::class, new CodeHasherException());
  }

  #[Test]
  public function generation_exceptions_extend_generation_exception(): void {
    $this->assertInstanceOf(GenerationException::class, new SnippetGenerationException());
    $this->assertInstanceOf(GenerationException::class, new ConfGenerationException());
    $this->assertInstanceOf(GenerationException::class, new UserGenerationException());
    $this->assertInstanceOf(GenerationException::class, new ThreadGenerationException());
    $this->assertInstanceOf(GenerationException::class, new HomeGenerationException());
  }

  #[Test]
  public function json_encoding_exception_extends_serialization_exception(): void {
    $this->assertInstanceOf(SerializationException::class, new JsonEncodingException());
  }

}
