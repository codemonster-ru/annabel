<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #fafafa;
            padding: 2rem;
        }

        table {
            border-collapse: collapse;
            width: 50%;
            background: #fff;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background: #f0f0f0;
            text-align: left;
        }
    </style>
</head>

<body>
    <h1>🧩 Xen Admin — Dashboard</h1>

    <h2>Loaded Modules</h2>
    <table>
        <tr>
            <th>Name</th>
            <th>Class</th>
        </tr>
        <?php foreach ($modules as $name => $class): ?>
            <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><code><?= htmlspecialchars($class) ?></code></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>