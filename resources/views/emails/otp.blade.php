@php
    $isAr = ($language ?? 'ar') === 'ar';
@endphp

@if($isAr)
مرحباً,

رمز التحقق الخاص بك هو: <strong>{{ $otp }}</strong>

هذا الرمز صالح لمدة {{ $expires }} دقائق فقط.

إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.

مع خالص التحية,
فريق متجر سامر
@else
Hello,

Your verification code is: <strong>{{ $otp }}</strong>

This code is valid for {{ $expires }} minutes only.

If you did not request this code, please ignore this message.

Best regards,
Samer Store Team
@endif
