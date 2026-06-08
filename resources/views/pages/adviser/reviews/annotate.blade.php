<x-layouts.fullpage :title="$paper->title">
    <x-research.annotation-viewer
        :paper="$paper"
        :pdf-url="$pdfUrl"
        :load-url="route('adviser.reviews.annotations.index', $paper)"
        :store-url="route('adviser.reviews.annotations.store', $paper)"
        :submit-url="route('adviser.reviews.annotate.submit', $paper)"
        :back-url="route('adviser.reviews.index')"
        :can-edit="true"
    />
</x-layouts.fullpage>
