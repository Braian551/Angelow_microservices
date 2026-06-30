<template>
  <main class="legal-page-root">
    <section class="legal-page-hero">
      <p class="legal-page-kicker">Información legal</p>
      <h1>Términos y condiciones</h1>
      <p>
        Consulta las reglas de uso, compra, pago, envío, postventa y tratamiento de datos personales de Angelow.
      </p>
      <div class="legal-page-meta" aria-label="Datos de versión">
        <span>Versión {{ termsMetadata.version }}</span>
        <span>Actualizado el {{ termsMetadata.updatedAtLabel }}</span>
        <span>{{ termsMetadata.country }}</span>
      </div>
    </section>

    <section class="legal-page-layout" aria-label="Contenido de términos y condiciones">
      <aside class="legal-page-index">
        <h2>Contenido</h2>
        <nav aria-label="Secciones legales">
          <a v-for="section in termsSections" :key="section.id" :href="`#${section.id}`">
            {{ section.title }}
          </a>
        </nav>
      </aside>

      <article class="legal-page-document">
        <section
          v-for="section in termsSections"
          :id="section.id"
          :key="section.id"
          class="legal-page-section"
        >
          <h2>{{ section.title }}</h2>
          <p v-for="paragraph in section.body" :key="paragraph">{{ paragraph }}</p>
        </section>

        <section class="legal-page-section legal-page-section--references">
          <h2>Referencias normativas</h2>
          <p>
            Esta vista se inspira en normas colombianas de protección de datos personales, comercio electrónico
            y protección al consumidor. No reemplaza asesoría legal especializada.
          </p>
          <ul>
            <li v-for="reference in legalReferences" :key="reference.href">
              <a :href="reference.href" target="_blank" rel="noopener noreferrer">{{ reference.label }}</a>
            </li>
          </ul>
        </section>
      </article>
    </section>
  </main>
</template>

<script setup>
import { legalReferences, termsMetadata, termsSections } from '../content/termsAndConditions'
import '../views/TermsAndConditionsView.css'
</script>
