@extends('layouts.app')

@section('content')
<div class="container flex items-center justify-center ">
    <div class="p-4 max-w-2xl text-left">
        <h1 class="text-3xl font-bold mb-4">Log</h1>
        <p class="text-sm mb-2 text-left leading-relaxed">
            <a href="https://loan.vdus.de" class="text-sm hover:underline text-yellow-700 mb-1">
                LOAN | Das Ausleihsystem für wissenschaftliche und medienpädagogische Einrichtungen
            </a>
             ist eine Plattform zur Inventarisierung und Ausleihe von Geräten. Außerdem bietet das System den Nutzenden die Möglichkeit, Räume zu verwalten und zu buchen.
        </p>
        <p class="text-sm mb-2 text-left leading-relaxed">
            Die Plattform wurde entwickelt, um eine Möglichkeit zur Katalogisierung und Prozessverwaltung im Rahmen einer nachvollziehbaren Geräteausleihe und Raumbuchung zu schaffen. Eine Rollen- und Rechteverwaltung sowie eine Prozesshistorie schaffen zudem Transparenz in Bezug auf Handlungsoptionen und Kontrolle.
        </p>
        <h2 class="text-2xl font-bold mb-4 mt-8">v1.2 | 14.08.2025</h2>
        <p class="text-sm mb-2 text-left leading-relaxed">
            <strong>Ergänzung um Filterung und Suchfunktion</strong>
            <ul class="list-disc text-sm ">
                <li>Suchfunktion nach Gerät, Gerätebeschreibung sowie Raum, Raumstandort und Raumbeschreibung</li>
                <li>Filterung nach Gerätekategorie, Gerätestatus sowie Raumstandort, Raumstatus</li>
            </ul>
        </p>
        <p class="text-sm my-2 text-left leading-relaxed">
            <strong>Ergänzung um Vormerkung, Stornierung und Übernahme einer zwischengespeicherten Vormerkung zur Ausleihe</strong>
            <ul class="list-disc text-sm ">
                <li>Mehrmalige Vormerkung eines Geräts für zukünftige Zeiträume</li>
                <li>Stornierung einer Vormerkung</li>
                <li>Ausleihe eines Geräts auf Basis einer zwischengespeicherten Vormerkung</li>
            </ul>
        </p>
        <p class="text-sm my-2 text-left leading-relaxed">
            Ergänzung um Erstellung, Bearbeitung und Löschung <strong>eigener Gerätekategorien</strong>
        </p>
        <h2 class="text-2xl font-bold mb-4 mt-8">v1.1 | 26.03.2025</h2>
            <ul class="list-disc text-sm ">
                <li>Frontend-Changes zur besseren Übersicht in den Bereichen Geräte, Räume und Nutzende</li>
            </ul>
        <h2 class="text-2xl font-bold mb-4 mt-8">v1.0 | 17.08.2025</h2>
        <p class="text-sm mb-2 text-left leading-relaxed">
            Erster Release: Die Plattform wurde entwickelt, um eine Möglichkeit zur Katalogisierung und Prozessverwaltung im Rahmen einer nachvollziehbaren Geräteausleihe und Raumbuchung zu schaffen. Eine Rollen- und Rechteverwaltung sowie eine Prozesshistorie schaffen zudem Transparenz in Bezug auf Handlungsoptionen und Kontrolle.
        </p>
    </div>
</div>
@endsection
