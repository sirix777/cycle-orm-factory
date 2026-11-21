<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Fixture\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(role: 'widget', table: 'widgets')]
final class Widget
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;
}
