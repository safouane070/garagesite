<x-error-page code="429" title="Even rustig aan"
    message="Je hebt kort achter elkaar veel verzoeken verstuurd. Wacht een minuutje en probeer het dan opnieuw.">
    <a href="{{ url()->previous() }}" class="btn btn-primary"><x-icon name="chevron-left" class="h-4 w-4" /> Terug</a>
    <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" /> Liever bellen: {{ config('brand.contact.phone') }}</a>
</x-error-page>
