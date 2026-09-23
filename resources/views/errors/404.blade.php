<x-error-page code="404" title="Deze pagina bestaat niet (meer)"
    message="Misschien is de auto die je zocht net verkocht, of klopt de link niet helemaal. In ons aanbod staat vast iets moois.">
    <a href="{{ route('cars.index') }}" class="btn btn-primary">Bekijk het aanbod <x-icon name="arrow-right" class="h-4 w-4" /></a>
    <a href="{{ route('home') }}" class="btn btn-outline">Naar de homepage</a>
</x-error-page>
