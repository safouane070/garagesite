<x-error-page code="419" title="Je sessie is verlopen"
    message="Uit veiligheid verloopt een formulier na een tijdje. Ga terug, vernieuw de pagina en probeer het opnieuw.">
    <a href="{{ url()->previous() }}" class="btn btn-primary"><x-icon name="chevron-left" class="h-4 w-4" /> Terug</a>
    <a href="{{ route('home') }}" class="btn btn-outline">Naar de homepage</a>
</x-error-page>
