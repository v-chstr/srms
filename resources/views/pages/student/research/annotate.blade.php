<x-layouts.fullpage :title="$paper->title">
    <x-research.annotation-viewer
        :paper="$paper"
        :pdf-url="$pdfUrl"
        :load-url="route('student.research.annotations.index', $paper->id)"
        :back-url="route('student.research.index')"
        :can-edit="false"
    />
</x-layouts.fullpage>
