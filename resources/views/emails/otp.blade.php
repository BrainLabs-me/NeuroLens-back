<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
    <style>
        /* Ovde možeš dodati dodatne CSS stilove po želji */
        body {
            margin: 0;
            padding: 0;
            background-color: #0D0F17;
            font-family: Arial, sans-serif;
            color: #FFFFFF;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            margin-bottom: 20px;
        }
        .header img {
            height: 40px;
        }
        .title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .otp-label {
            margin-bottom: 8px;
        }
        .otp-code {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .otp-box {
            width: 50px;
            height: 50px;
            background-color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            border-radius: 4px;
        }
        .footer-text {
            font-size: 12px;
            color: #aaa;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo i zaglavlje -->
        <div class="header">
            {{-- <img src="{{ asset('images/logo.png') }}" alt="NeuroLens Logo"> --}}
        </div>
        
        <!-- Naslov resetovanja lozinke -->
        <h1 class="title">Password Reset</h1>
        
        <!-- Label za OTP -->
        <p class="otp-label">Your OTP code is</p>
        
        <!-- Polja za 4-cifreni OTP -->
        <div class="otp-code">
            <div class="otp-box">{{-- Prva cifra --}}</div>
            <div class="otp-box">{{-- Druga cifra --}}</div>
            <div class="otp-box">{{-- Treća cifra --}}</div>
            <div class="otp-box">{{-- Četvrta cifra --}}</div>
        </div>
        
        <!-- Informacija o isteku koda -->
        <p>This code will expire in 10 minutes</p>
        
        <!-- Pozdrav i potpis -->
        <p>Regards,<br>NeuroLens</p>
        
        <!-- Dodatni tekst u dnu -->
        <p class="footer-text">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id volutpat quam. 
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id volutpat quam. 
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id volutpat quam.
        </p>
    </div>
</body>
</html>
