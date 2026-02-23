import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import HomeView from './modules/home/views/HomeView';
import RegisterView from './modules/auth/views/RegisterView';
import LoginView from './modules/auth/views/LoginView';
import './styles/variables.css';

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<HomeView />} />
        <Route path="/register" element={<RegisterView />} />
        <Route path="/login" element={<LoginView />} />
      </Routes>
    </Router>
  );
}

export default App;
