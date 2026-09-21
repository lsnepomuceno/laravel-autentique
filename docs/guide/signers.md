# Signers

A `Data\Input\Signer` is someone asked to act on a document, and how Autentique
reaches them. It is built through the named constructor for that way, so it can
never lack an address:

| Constructor | Autentique | Cost per request |
|---|---|---|
| `Signer::email($email)` | emails the request | the lowest |
| `Signer::whatsapp('+5554999999999')` | sends it by WhatsApp | higher |
| `Signer::sms('+5554999999999')` | sends it by SMS, Brazilian documents only | the highest |
| `Signer::link('Ronaldo')` | sends nothing; the document carries a link for you to deliver | paid by the method the signer picks |
| `Signer::link('João', phone: '+55…')`, `link('João', email: '…')` | as above, and only that address can sign through the link | as above |

Phones are written in international format, `+5554999999999`; anything else is
refused before sending.

## What they are asked to do

The action is the second argument, `Action::Sign` by default:

```php
Signer::email('witness@example.com', Action::SignAsWitness);
```

`Sign`, `Approve`, `Recognize`, `SignAsWitness`, `AcknowledgeReceipt`,
`EndorseInBlack`, `EndorseInWhite`. Autentique's two deprecated actions are not
offered.

## Everything else

A signer is immutable, and each `with…()` returns a new one:

```php
$signer = Signer::email('ana@example.com')
    ->withName('Ana Souza')
    ->withPosition(Position::signature(x: 50, y: 90))
    ->withPosition(Position::date(x: 50, y: 95))
    ->withVerification(SecurityVerification::sms())
    ->withCpf('123.456.789-09')      // only the holder of this CPF can sign
    ->qualified();                   // a qualified certificate, Corporate plan
```

Where to stamp is in [positions](/guide/positions), and identity checks in
[security verifications](/guide/security-verifications).
