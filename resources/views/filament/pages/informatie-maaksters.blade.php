<x-filament::page>


    <div class="prose  max-w-none">
        Aan de linkerkant van het scherm zie je de volgende onderwerpen staan
        <img src="{{ asset('/images/menu_maakster.png') }}" alt="menu_maakster">
    </div>
    <hr />
    <div class="prose  max-w-none">
        <h1 class="filament-header-heading text-2xl font-bold trafcking-tight">Beschikbaar</h1>
        <span>Dit zijn kledingverzoeken waarop je kunt inschrijven. Rechts van beschikbaar staat een getal. Dit zijn de aanvragen die nog openstaan. In het voorbeeld hierboven zijn dat er dus 45.</span>
        <img src="{{ asset('/images/aangeboden.png') }}" alt="aangeboden">

        <span>Bij de beschikbare kleding kan je rechts bovenin een zoekopdracht typen (in het voorbeeld hierboven jongen 116).<br><br>
            Door op het oranje trechter icoontje naast de zoekbalk te klikken, kan je filteren op instantie. Zo kan je bijvoorbeeld makkelijk alle BS aanvragen zien, of kiezen voor een aanvraag van dezelfde instantie waarvoor je al een aanvraag opgepakt of gemaakt hebt.<br><br>
            Door op de knop Oppakken te klikken, komt deze aanvraag direct op jouw naam te staan. Deze aanvraag gaat nu automatisch uit de lijst aangeboden naar jouw lijst onder 'openstaand'.
            Er komt dan een melding in het scherm dat de aanvraag toegewezen is.
        </span>

    </div>
    <hr />
    <h2 class="filament-header-heading text-2xl font-bold trafcking-tight">Mijn aanvragen</h2>

    Dit zijn kledingaanvragen die je hebt opgepakt. In het voorbeeld hierboven zijn dat er 10.
    <img src="{{ asset('/images/openstaand.png') }}" alt="openstaand">
    <div class="mx-auto prose dark:prose-invert max-w-none">
        Ben je klaar met je aanvraag, kun je kiezen uit 2 mogelijkheden om je aanvraag af te ronden.


        <ul class="list-disc">
            <li>Klaar betekent dat je aanvraag klaar is maar nog niet verstuurd.</li>
            <li>Verzonden betekent dat je aanvraag klaar en verstuurd is naar de instantie.</li>
        </ul>
        Als je je aanvraag direct na het maken verstuurt, hoef je alleen de knop "verzonden" te gebruiken. Je aanvraag wordt dan ook gelijk "klaar" gemeld.

    </div>
    <hr />
    <div class="prose  max-w-none">
        Verstuur je je aanvragen in 1x naar een instantie? Klik dan op het vierkantje voor de aanvraagcode. Met de 3 puntjes kun je dan klikken op verzonden en zijn je aanvragen in 1x afgemeld. Dan zijn ze ook niet meer zichtbaar voor jou.

        <img src="{{ asset('/images/opties_afmelden.png') }}" alt="afmelden">

        <h2 class="filament-header-heading text-2xl font-bold trafcking-tight">Openstaand</h2>
        Als je op de regel van de aanvraag klikt, zie je verdere informatie.<br>
        Zoals het adres waar de kleding naar toegestuurd moet worden.<br>

        <img src="{{ asset('/images/details.png') }}" alt="details">



    </div>
</x-filament::page>
