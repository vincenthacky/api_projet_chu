<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerte de Sécurité</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .content {
            padding: 30px;
        }
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table th,
        .info-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .security-tips {
            background-color: #e3f2fd;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .risk-high {
            color: #dc3545;
            font-weight: bold;
        }
        .risk-medium {
            color: #fd7e14;
            font-weight: bold;
        }
        .risk-low {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="alert-icon">🔒</div>
            <h1>{{ $alertType === 'suspicious_login' ? 'Connexion Suspecte Détectée' : 'Nouvelle Connexion Détectée' }}</h1>
            <p>Bonjour {{ $userName }}, nous avons détecté une activité inhabituelle sur votre compte</p>
        </div>

        <div class="content">
            @if($alertType === 'suspicious_login')
                <div class="alert-box">
                    <strong>⚠️ Attention :</strong> Une connexion suspecte a été détectée sur votre compte. 
                    Si ce n'était pas vous, veuillez prendre des mesures immédiates pour sécuriser votre compte.
                </div>
            @else
                <div class="alert-box">
                    <strong>ℹ️ Information :</strong> Une connexion depuis un nouvel appareil a été détectée sur votre compte.
                </div>
            @endif

            <h3>Détails de la connexion :</h3>
            <table class="info-table">
                <tr>
                    <th>📅 Date et heure</th>
                    <td>{{ \Carbon\Carbon::parse($alertData['timestamp'])->format('d/m/Y à H:i:s') }}</td>
                </tr>
                <tr>
                    <th>🌍 Adresse IP</th>
                    <td>{{ $alertData['ip_address'] }}</td>
                </tr>
                <tr>
                    <th>📱 Appareil</th>
                    <td>{{ $alertData['user_agent'] }}</td>
                </tr>
                @if(isset($alertData['location']['country']))
                <tr>
                    <th>📍 Localisation</th>
                    <td>
                        {{ $alertData['location']['city'] ?? 'Ville inconnue' }}, 
                        {{ $alertData['location']['country'] ?? 'Pays inconnu' }}
                    </td>
                </tr>
                @endif
                <tr>
                    <th>⚠️ Niveau de risque</th>
                    <td>
                        <span class="risk-{{ $alertData['risk_level'] }}">
                            {{ ucfirst($alertData['risk_level']) }}
                            @if($alertData['risk_level'] === 'high') 🔴
                            @elseif($alertData['risk_level'] === 'medium') 🟡
                            @else 🟢
                            @endif
                        </span>
                    </td>
                </tr>
            </table>

            @if(!empty($alertData['reasons']))
            <h4>Raisons de l'alerte :</h4>
            <ul>
                @foreach($alertData['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <h3>Si c'était vous :</h3>
                <a href="{{ $trustUrl }}?token={{ $alertData['token'] ?? '' }}" class="button btn-primary">
                    ✅ Oui, c'était moi - Marquer cet appareil comme fiable
                </a>
                
                <h3 style="margin-top: 30px;">Si ce n'était PAS vous :</h3>
                <a href="{{ $changePasswordUrl }}" class="button btn-danger">
                    🔒 Changer mon mot de passe immédiatement
                </a>
            </div>

            <div class="security-tips">
                <h4>💡 Conseils de sécurité :</h4>
                <ul>
                    <li><strong>Ne partagez jamais</strong> vos identifiants de connexion</li>
                    <li><strong>Utilisez un mot de passe unique</strong> et complexe</li>
                    <li><strong>Activez l'authentification à deux facteurs</strong> si disponible</li>
                    <li><strong>Déconnectez-vous</strong> des appareils publics ou partagés</li>
                    <li><strong>Vérifiez régulièrement</strong> l'activité de votre compte</li>
                </ul>
            </div>

            @if($alertType === 'suspicious_login')
            <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h4 style="color: #721c24; margin-top: 0;">🚨 Mesures recommandées :</h4>
                <ol style="color: #721c24;">
                    <li>Changez immédiatement votre mot de passe</li>
                    <li>Vérifiez si d'autres comptes utilisent le même mot de passe</li>
                    <li>Examinez l'activité récente de votre compte</li>
                    <li>Contactez le support si vous suspectez une compromission</li>
                </ol>
            </div>
            @endif
        </div>

        <div class="footer">
            <p>
                Cet email a été envoyé automatiquement par notre système de sécurité.<br>
                Si vous avez des questions, contactez notre support technique.
            </p>
            <p style="font-size: 12px; color: #999;">
                Ne répondez pas à cet email. Il s'agit d'une adresse automatisée.
            </p>
        </div>
    </div>
</body>
</html>