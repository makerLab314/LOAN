@extends('layouts.app')

@section('content')
<div class="container flex items-center justify-center ">
    <div class="p-2 max-w-2xl text-center">
        <h1 class="text-3xl font-bold mb-6">Impressum</h1>
        
        <p class="text-sm mb-8">Angaben gemäß § 5 TMG</p>
        <p class="text-sm">Vincent Dusanek</p>
        <p class="mb-4 text-sm"><a href="mailto:vincent.dusanek@gmail.com" class="hover:underline text-yellow-700 pl-1 mb-1">vincent.dusanek[at]gmail.com</a></p>

        <h2 class="text-2xl font-bold text-center mt-6 mb-2">Website</h2>
        <p class="text-gray-600">
            <span class="font-bold text-sm">LOAN | Das Ausleihsystem für wissenschaftliche und medienpädagogische Einrichtungen</span>
            <br>
            <a href="https://loan.vdus.de" class="text-sm hover:underline text-yellow-700 pl-1 mb-1">http://loan.vdus.de</a>
        </p>

        <h2 class="text-2xl font-bold text-center mt-6 mb-2">Haftung für Inhalte</h2>
        <p class="text-justify leading-relaxed text-sm">
            Als Diensteanbieter bin ich gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. 
            Nach §§ 8 bis 10 TMG bin ich als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen 
            zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen. Verpflichtungen zur Entfernung 
            oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung 
            ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden von entsprechenden 
            Rechtsverletzungen werde ich diese Inhalte umgehend entfernen.
        </p>

        <h2 class="text-2xl font-bold text-center mt-6 mb-2">Urheberrecht & Lizenzen</h2>
        <p class="text-justify leading-relaxed text-sm">
            Die auf dieser Website verwendete Software basiert auf Laravel und TailwindCSS und wird unter der MIT-Lizenz veröffentlicht. 
            Alle selbst erstellten Inhalte und Werke auf diesen Seiten unterliegen, sofern nicht anders angegeben, ebenfalls der MIT-Lizenz.
        </p>

        <h2 class="text-2xl font-bold text-center mt-6 mb-2">Hosting & Serverstandort</h2>
        <p class="text-justify leading-relaxed text-sm">
            Die Website wird auf einem Server der Hetzner Online GmbH, Standort Nürnberg, gehostet. Die Domain wurde über Strato AG registriert.
        </p>
    </div>
</div>
@endsection
