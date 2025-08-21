<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: auto;
        }
        h1 {
            color: #4CAF50;
        }
        a.button {
            display: inline-block;
            padding: 12px 25px;
            margin: 15px 0;
            background-color: #4CAF50;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }
        p {
            margin: 10px 0;
        }
        .footer {
            font-size: 0.9em;
            color: #777;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Réinitialisation du mot de passe</h1>

        <p>Bonjour {{ $user->nom ?? $user->email }},</p>

        <p>Vous avez demandé à réinitialiser votre mot de passe.</p>

        <p>Cliquez ci-dessous pour continuer :</p>

        <p>
            <a href="{{ $resetUrl }}" class="button">Réinitialiser mon mot de passe</a>
        </p>

        <p>Ce lien expirera dans 60 minutes.</p>

        <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer ce message.</p>

        <p>Merci,</p>
        <p>L'équipe {{ config('app.name') }}</p>

        <p class="footer">Si vous avez des questions, n'hésitez pas à nous contacter.</p>
    </div>
</body>
</html>
