# Security verifications

An additional identity check a signer goes through before signing. **Each one
consumes verification credits**, and a lack of them fails with the code
`unavailable_verifications_credits`.

| Constructor | The signer |
|---|---|
| `SecurityVerification::sms($phone = null)` | types a code sent by SMS; without a phone, they provide one |
| `SecurityVerification::manual()` | sends a photo ID and a selfie, which you approve or reject |
| `SecurityVerification::upload()` | sends the front and back of a photo ID |
| `SecurityVerification::live()` | sends a photo ID, a selfie and a liveness video, matched automatically |
| `SecurityVerification::pfFacial()` | takes a selfie SERPRO checks against the Brazilian government's photo for their CPF |
| `SecurityVerification::biometricAndTextExtraction()` | a photo ID and a selfie, with the ID's data extracted and compared |
| `SecurityVerification::livenessAndTextExtraction()` | liveness and a photo ID, with the data extracted and compared |

```php
Signer::email('ana@example.com')
    ->withVerification(SecurityVerification::sms())
    ->withVerification(SecurityVerification::live()->withoutFallback());
```

**A signer can have only one of `manual`, `upload`, `live` and `pfFacial`**, and
SMS beside it. Two are refused before sending.

## When an automatic check fails

`upload`, `live`, `pfFacial` and `biometricAndTextExtraction` fall back to a
manual check after their last attempt, which you then approve or reject. With
`withoutFallback()`, the document is rejected instead.

A manual check waiting for you arrives as a `signature.biometric_unapproved`
webhook, and each `Data\Verification` on a signature carries the `$id` and the
`$images` to decide on.
