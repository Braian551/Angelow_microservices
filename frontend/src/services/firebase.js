import { getApp, getApps, initializeApp } from 'firebase/app'
import { getAuth, GoogleAuthProvider } from 'firebase/auth'

// Configuración leída desde Vite; si falta una clave obligatoria se desactiva Firebase.
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY || '',
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN || '',
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID || '',
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET || '',
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID || '',
  appId: import.meta.env.VITE_FIREBASE_APP_ID || '',
  measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID || '',
}

// Valida las claves mínimas para evitar inicializar Firebase con configuración incompleta.
const isFirebaseReady = Boolean(
  firebaseConfig.apiKey
  && firebaseConfig.authDomain
  && firebaseConfig.projectId
  && firebaseConfig.appId,
)

// Reutiliza la app existente en HMR o crea una instancia nueva cuando aún no existe.
const firebaseApp = isFirebaseReady
  ? (getApps().length ? getApp() : initializeApp(firebaseConfig))
  : null

// Expone Auth y proveedor Google solo cuando la configuración está completa.
const firebaseAuth = firebaseApp ? getAuth(firebaseApp) : null
const googleProvider = new GoogleAuthProvider()
googleProvider.setCustomParameters({ prompt: 'select_account' })

export { firebaseAuth, googleProvider, isFirebaseReady }

