<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Lib\Core\Database;
use Lib\Core\Model;

Database::configure([
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        ],
    ],
]);

class TestUser extends Model
{
    protected string $table = 't_users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['name', 'email', 'age', 'active', 'role_id'];
    protected array $casts = ['age' => 'int', 'active' => 'bool'];
    protected bool $timestamps = false;

    public function posts(): array
    {
        return $this->hasMany(TestPost::class, 'user_id', 'id');
    }
}

class TestPost extends Model
{
    protected string $table = 't_posts';
    protected string $primaryKey = 'id';
    protected array $fillable = ['user_id', 'title'];
    protected bool $timestamps = false;

    public function user(): array
    {
        return $this->belongsTo(TestUser::class, 'user_id', 'id');
    }
}

class TestStamp extends Model
{
    protected string $table = 't_stamps';
    protected string $primaryKey = 'id';
    protected array $fillable = ['name'];
    protected bool $timestamps = true;
}

$checks = 0;
$failures = [];

function check(string $label, bool $ok): void
{
    global $checks, $failures;
    $checks++;

    if (!$ok) {
        $failures[] = $label;
    }
}

function expectException(string $label, callable $fn, string $class = \InvalidArgumentException::class): void
{
    try {
        $fn();
        check($label, false);
    } catch (\Throwable $e) {
        check($label, $e instanceof $class);
    }
}

$pdo = Database::connection();
$pdo->exec('CREATE TABLE t_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    email TEXT,
    age INTEGER,
    active INTEGER,
    role_id INTEGER NULL,
    created_at TEXT NULL,
    updated_at TEXT NULL
)');
$pdo->exec('CREATE TABLE t_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT
)');
$pdo->exec('CREATE TABLE t_stamps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    created_at TEXT NULL,
    updated_at TEXT NULL
)');

check('driver sqlite', Database::isSqlite());
check('quote identifier sqlite', Database::quoteIdentifier('id') === '"id"');

$id1 = (new TestUser())->insert([
    'name' => 'Ana',
    'email' => 'ana@example.com',
    'age' => 25,
    'active' => true,
    'role_id' => 1,
    'ignored' => 'nope',
]);
$id2 = (new TestUser())->insert([
    'name' => 'Beto',
    'email' => 'beto@example.com',
    'age' => 40,
    'active' => true,
    'role_id' => 2,
]);
$id3 = (new TestUser())->insert([
    'name' => 'Carla',
    'email' => 'carla@example.com',
    'age' => 17,
    'active' => false,
]);

check('insert returns int', $id1 === 1 && $id2 === 2 && $id3 === 3);

$ana = (new TestUser())->find($id1);
check('find by primary key', $ana !== null && $ana['name'] === 'Ana');
check('fillable filters unknown columns', !array_key_exists('ignored', $ana));
check('casts applied on save (bool)', (int) $ana['active'] === 1);
check('find missing returns null', (new TestUser())->find(999) === null);

$adults = TestUser::query()->where('age', 18, '>')->get();
check('where with > operator', count($adults) === 2);

$liked = TestUser::query()->where('name', 'an%', 'LIKE')->get();
check('where with LIKE operator', count($liked) === 1 && $liked[0]['name'] === 'Ana');

check('whereIn', count(TestUser::query()->whereIn('id', [$id1, $id2])->get()) === 2);
check('whereIn empty matches nothing', TestUser::query()->whereIn('id', [])->count() === 0);
check('orWhere', count(TestUser::query()->where('name', 'Ana')->orWhere('name', 'Beto')->get()) === 2);
check('whereNull', TestUser::query()->whereNull('role_id')->count() === 1);
check('whereNotNull', TestUser::query()->whereNotNull('role_id')->count() === 2);
check('where null value uses IS NULL', TestUser::query()->where('role_id', null)->count() === 1);
check('where != null uses IS NOT NULL', TestUser::query()->where('role_id', null, '!=')->count() === 2);

$affected = TestUser::query()->where('age', 30, '>')->update(['active' => false]);
check('update honours > operator', $affected === 1);
check('update only touched matching row', (int) (new TestUser())->find($id2)['active'] === 0);
check('update left other rows', (int) (new TestUser())->find($id1)['active'] === 1);

check('update chained where', TestUser::query()->where('name', 'Ana')->update(['age' => 26]) === 1);
check('updated value persisted', (int) (new TestUser())->find($id1)['age'] === 26);

check(
    'update explicit conditions (legacy API)',
    (new TestUser())->update(['name' => 'Ana Maria'], ['id' => $id1]) === 1
);
check('explicit conditions applied', (new TestUser())->find($id1)['name'] === 'Ana Maria');

$tmp1 = (new TestUser())->insert(['name' => 'Temp', 'email' => 'tmp@example.com', 'age' => 99]);
check('delete honours operator', TestUser::query()->where('age', 90, '>')->delete() === 1);
check('delete removed row', (new TestUser())->find($tmp1) === null);
check('delete left other rows', TestUser::query()->count() === 3);

$tmp2 = (new TestUser())->insert(['name' => 'Temp2', 'email' => 'tmp2@example.com', 'age' => 30]);
check('delete explicit conditions (legacy API)', (new TestUser())->delete(['id' => $tmp2]) === 1);
check('delete explicit removed row', (new TestUser())->find($tmp2) === null);

expectException('update without conditions throws', fn () => (new TestUser())->update(['name' => 'x']));
expectException('delete without conditions throws', fn () => (new TestUser())->delete());
expectException(
    'invalid orderBy identifier throws',
    fn () => TestUser::query()->orderBy('id; DROP TABLE t_users')->get()
);
expectException(
    'invalid operator throws',
    fn () => TestUser::query()->where('age', 1, '==')->get()
);
check('tables still present after injection attempt', TestUser::query()->count() === 3);

TestUser::query()->update(['role_id' => 1], ['id' => $id2]);
$groups = TestUser::query()->select('role_id')->groupBy('role_id')->get();
check('groupBy groups rows', count($groups) === 2);
check('select restricts columns', array_keys($groups[0]) === ['role_id']);
check('count with groupBy', TestUser::query()->select('role_id')->groupBy('role_id')->count() === 2);
check('paginate total with groupBy', TestUser::query()->groupBy('role_id')->paginate(1, 1)->total() === 2);

$page1 = TestUser::query()->orderBy('id')->paginate(2, 1);
check('paginate page size', $page1->count() === 2);
check('paginate total', $page1->total() === 3);
check('paginate current page', $page1->currentPage() === 1);
check('paginate last page', $page1->lastPage() === 2);
check('paginate has more', $page1->hasMorePages() === true);

$page2 = TestUser::query()->orderBy('id')->paginate(2, 2);
check('paginate second page', $page2->count() === 1 && $page2->hasMorePages() === false);
check('paginate toArray keys', array_keys($page1->toArray()) === ['data', 'total', 'per_page', 'current_page', 'last_page']);

$cast = Database::raw('SELECT age, active FROM t_users WHERE id = ?', [$id1])[0];
check('age cast to int in DB', (int) $cast['age'] === 26);
check('active cast to int in DB', (int) $cast['active'] === 1);

$stamp = new TestStamp();
$stampId = $stamp->insert(['name' => 'a']);
$stampRow = Database::raw('SELECT created_at, updated_at FROM t_stamps WHERE id = ?', [$stampId])[0];
check('timestamps set on insert', $stampRow['created_at'] !== null && $stampRow['updated_at'] !== null);
$stamp->update(['name' => 'b'], ['id' => $stampId]);
$stampRow = Database::raw('SELECT updated_at FROM t_stamps WHERE id = ?', [$stampId])[0];
check('timestamps set on update', $stampRow['updated_at'] !== null);

$userRow = Database::raw('SELECT created_at FROM t_users WHERE id = ?', [$id1])[0];
check('timestamps disabled leaves nulls', $userRow['created_at'] === null);

$post1 = (new TestPost())->insert(['user_id' => $id1, 'title' => 'Primero']);
(new TestPost())->insert(['user_id' => $id1, 'title' => 'Segundo']);
(new TestPost())->insert(['user_id' => $id2, 'title' => 'Tercero']);

$withPosts = TestUser::query()->with('posts')->orderBy('id')->get();
check('eager load hasMany count', count($withPosts[0]['posts']) === 2);
check('eager load hasMany contents', array_column($withPosts[0]['posts'], 'title') === ['Primero', 'Segundo']);
check('eager load hasMany empty', $withPosts[2]['posts'] === []);

$post = TestPost::query()->with('user')->where('id', $post1)->first();
check('eager load belongsTo', $post['user']['name'] === 'Ana Maria');
check('eager load belongsTo nested key present', array_key_exists('user', $post));

check('exists true', TestUser::query()->where('id', $id1)->exists());
check('exists false', !TestUser::query()->where('id', 999)->exists());
check('first returns null', TestUser::query()->where('id', 999)->first() === null);
check('pluck', TestUser::query()->orderBy('id')->pluck('name') === ['Ana Maria', 'Beto', 'Carla']);

$rawRows = (new TestUser())->raw('SELECT COUNT(*) AS total FROM t_users');
check('raw select returns arrays', is_array($rawRows) && (int) $rawRows[0]['total'] === 3);
check('raw update returns affected rows', (new TestUser())->raw('UPDATE t_users SET role_id = 5 WHERE role_id = 1') === 2);

expectException(
    'unknown validator rule throws',
    fn () => \Lib\Support\Validator::make(['x' => '1'], ['x' => 'foo'])->passes()
);

$_SERVER['CONTENT_TYPE'] = 'application/json';
$_SERVER['HTTP_X_CUSTOM'] = 'abc';
$headerRequest = new \Lib\Core\Request();
check('header maps Content-Type', $headerRequest->header('Content-Type') === 'application/json');
check('header maps X-Custom', $headerRequest->header('X-Custom') === 'abc');
unset($_SERVER['CONTENT_TYPE'], $_SERVER['HTTP_X_CUSTOM']);

echo "\n{$checks} comprobaciones, " . count($failures) . " fallidas\n";

foreach ($failures as $failure) {
    echo "  FALLO: {$failure}\n";
}

exit($failures === [] ? 0 : 1);
