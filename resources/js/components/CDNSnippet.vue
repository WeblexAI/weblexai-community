<template>
    <pre><code class="text-white">
<span class="text-blue-400">&lt;link</span> <span class="text-green-400">rel=</span><span class="text-yellow-300">"stylesheet"</span> <span class="text-green-400">href=</span><span class="text-yellow-300">"{{ $page.props.cdn_url }}/wlai/weblexai.css"</span><span class="text-blue-400">/&gt;</span>
<span class="text-blue-400">&lt;script</span> <span class="text-green-400">type=</span><span class="text-yellow-300">"text/javascript"</span> <span class="text-green-400">src=</span><span class="text-yellow-300">"{{ $page.props.cdn_url }}/wlai/weblexai.min.js"</span><span class="text-blue-400">&gt;&lt;/script&gt;</span>
<span class="text-blue-400">&lt;script&gt;</span>
  <span class="text-purple-400">WeblexAI</span>.<span class="text-teal-400">init</span>(<span class="text-yellow-300">'YOUR_PROJECT_API_KEY'</span>);
<span class="text-blue-400">&lt;/script&gt;</span>
  </code></pre>
</template>

<style scoped></style>
