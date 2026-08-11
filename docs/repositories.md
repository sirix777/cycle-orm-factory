# Repository Services

`RepositoryFactory` makes a custom Cycle repository available as a container service. Application services can therefore depend on a repository interface instead of `Cycle\ORM\ORMInterface`.

The factory reads the compiled Cycle schema to find the role assigned to the requested repository class, then returns Cycle's repository for that role. The entity schema is the only mapping required.

## Configure an entity repository

Set the repository class on the entity. The repository must implement `Cycle\ORM\RepositoryInterface`; extending `Cycle\ORM\Select\Repository` fulfils this requirement.

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: UserRepository::class)]
final class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $email;
}
```

The same mapping can be placed in a manual schema definition when annotations are not used:

```php
use App\Repository\UserRepository;
use Cycle\ORM\SchemaInterface;

return [
    'cycle' => [
        'schema' => [
            'manual_mapping_schema_definitions' => [
                'user' => [
                    SchemaInterface::REPOSITORY => UserRepository::class,
                    // Other required entity schema fields.
                ],
            ],
        ],
    ],
];
```

## Create a read repository

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Cycle\ORM\Select\Repository;

final class UserRepository extends Repository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        $user = $this->select()
            ->where('email', $email)
            ->fetchOne();

        return $user instanceof User ? $user : null;
    }
}
```

## Add persist methods

Cycle repositories are read-only by default, but a custom repository can create an `EntityManager` and expose explicit write methods. Cycle constructs the custom repository and supplies both `Select` and `ORMInterface`.

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;

final class UserRepository extends Repository implements UserRepositoryInterface
{
    private EntityManager $entityManager;

    public function __construct(Select $select, ORMInterface $orm)
    {
        parent::__construct($select);

        $this->entityManager = new EntityManager($orm);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->run();
    }

    public function delete(User $user): void
    {
        $this->entityManager->delete($user);
        $this->entityManager->run();
    }
}
```

For writes spanning several repositories, coordinate persistence in an application service so that transaction boundaries remain explicit.

## Register the container service

Register the concrete repository class with `RepositoryFactory`, then map the application interface to that concrete service:

```php
<?php

declare(strict_types=1);

use App\Repository\UserRepository;
use App\Repository\UserRepositoryInterface;
use Sirix\Cycle\Factory\RepositoryFactory;

return [
    'dependencies' => [
        'factories' => [
            UserRepository::class => RepositoryFactory::class,
        ],
        'aliases' => [
            UserRepositoryInterface::class => UserRepository::class,
        ],
    ],
];
```

With Laminas ServiceManager, resolving `UserRepositoryInterface::class` follows its alias and invokes the factory with `UserRepository::class` as the requested service name. The factory uses that exact class name to search the schema.

Application code can now depend on the interface:

```php
final class RegisterUser
{
    public function __construct(private UserRepositoryInterface $users) {}
}
```

## Roles and constraints

An entity role can be any non-empty string:

```php
#[Entity(role: 'user', repository: UserRepository::class)]
final class User {}
```

The factory finds this role and calls `$orm->getRepository('user')`; it does not need the entity class name.

Each repository class registered with `RepositoryFactory` must be assigned to exactly one entity role. The factory throws an `InvalidArgumentException` when no role or multiple roles match. This prevents an accidental arbitrary selection when one repository class is reused for several entities.

## Compiled schema cache

When `cycle.schema.cache.enabled=true`, Cycle reads repository mappings from the compiled schema file. Rebuild the schema after adding or changing `repository:` or a manual schema mapping:

```bash
php vendor/bin/laminas cycle:schema:compile
```

See the main README for the configured command integration and schema-cache settings.
