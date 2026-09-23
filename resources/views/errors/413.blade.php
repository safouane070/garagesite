<x-error-page code="413" title="Upload te groot"
    message="De bestanden zijn samen te groot om in één keer te versturen. Ga terug en upload de foto's in kleinere groepjes.">
    <a href="{{ url()->previous() }}" class="btn btn-primary"><x-icon name="chevron-left" class="h-4 w-4" /> Terug</a>
</x-error-page>
