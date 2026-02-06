#!/usr/bin/env php
<?php
// Usage:
// php bin/hash_to_sql.php insert <matricule> <nom> <prenom> <email> <id_role> <plainPassword>
// php bin/hash_to_sql.php update-password <email> <plainPassword>

if ($argc < 2) {
    echo "Usage:\n";
    echo "  php bin/hash_to_sql.php insert <matricule> <nom> <prenom> <email> <id_role> <plainPassword>\n";
    echo "  php bin/hash_to_sql.php update-password <email> <plainPassword>\n";
    exit(1);
}

$cmd = $argv[1];

function sql_escape(string $s): string {
    // escape single quotes for SQL
    return str_replace("'", "''", $s);
}

if ($cmd === 'insert') {
    if ($argc !== 8) {
        fwrite(STDERR, "insert requires 6 arguments\n");
        exit(2);
    }
    $matricule = intval($argv[2]);
    $nom = sql_escape($argv[3]);
    $prenom = sql_escape($argv[4]);
    $email = sql_escape($argv[5]);
    $idRole = intval($argv[6]);
    $plain = $argv[7];

    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $hashEsc = sql_escape($hash);

    $sql = "INSERT INTO utilisateur (matricule, nom, prenom, email, password, id_role) VALUES (" .
        $matricule . ", '" . $nom . "', '" . $prenom . "', '" . $email . "', '" . $hashEsc . "', " . $idRole . ");";

    echo $sql . PHP_EOL;
    exit(0);

} elseif ($cmd === 'update-password') {
    if ($argc !== 4) {
        fwrite(STDERR, "update-password requires 2 arguments\n");
        exit(2);
    }
    $email = sql_escape($argv[2]);
    $plain = $argv[3];

    $hash = password_hash($plain, PASSWORD_BCRYPT);
    $hashEsc = sql_escape($hash);

    $sql = "UPDATE utilisateur SET password = '" . $hashEsc . "' WHERE email = '" . $email . "';";
    echo $sql . PHP_EOL;
    exit(0);

} else {
    fwrite(STDERR, "Unknown command: $cmd\n");
    exit(3);
}
