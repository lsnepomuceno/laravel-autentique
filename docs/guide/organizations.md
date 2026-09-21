# Organizations and templates

## Organizations

```php
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

$organization = Autentique::organizations()->current();   // the one the token acts in
$organization->groups;                                     // list<Group>, "Administrador" and "Sem grupo" by default

foreach (Autentique::organizations()->list() as $organization) {
    $organization->id;    // what forOrganization(), documents()->sign() and transfer() take
}
```

The organization's `$id` is an integer; its `$uuid` is a string. Operations take
the integer.

Groups are read through their organization. The API documents no way to create,
change or remove them.

## Email templates

```php
$page = Autentique::organizations()->emailTemplates();

foreach ($page as $template) {
    $template->id;     // what newDocument(...)->emailTemplate($id) takes
    $template->type;   // EmailTemplateType::Solicitation or Completed
}
```

Templates are created in the dashboard; the API only lists them.

## Document templates are not available

**The API cannot create a document from a dashboard template.** Autentique says
so in its documentation, and offers an alternative: keep the template as an
HTML file of your own, fill it, and upload the HTML as the document's file.

```php
use LSNepomuceno\LaravelAutentique\Contracts\FileSource;

final readonly class RenderedHtml implements FileSource
{
    public function __construct(private string $html, private string $name) {}

    public function name(): string
    {
        return $this->name;          // the .html extension tells Autentique what it is
    }

    public function contents(): string
    {
        return $this->html;
    }
}

Autentique::newDocument('Service agreement')
    ->file(new RenderedHtml(view('contracts.agreement', compact('customer'))->render(), 'agreement.html'))
    ->signer(Signer::email($customer->email))
    ->send();
```

The HTML never touches the disk: any `Contracts\FileSource` can be uploaded.
