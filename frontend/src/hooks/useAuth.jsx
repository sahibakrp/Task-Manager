import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import axios from 'axios';

const AuthContext = createContext(null);
const BASE_URL = import.meta.env.VITE_API_BASE || 'http://localhost/Task-Manager/backend';

function parseJwtPayload(token) {
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
    return payload;
  } catch {
    return null;
  }
}

export function AuthProvider({ children }) {
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('user');
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch {
        return null;
      }
    }
    const existingToken = localStorage.getItem('token');
    if (existingToken) {
      return parseJwtPayload(existingToken);
    }
    return null;
  });

  useEffect(() => {
    if (token) {
      localStorage.setItem('token', token);
      if (!user) {
        const payload = parseJwtPayload(token);
        if (payload) {
          setUser(payload);
        }
      }
    } else {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  }, [token]);

  useEffect(() => {
    if (user) {
      localStorage.setItem('user', JSON.stringify(user));
    }
  }, [user]);

  const login = async (email, password) => {
    const response = await axios.post(`${BASE_URL}/login`, { email, password });
    setToken(response.data.token);
    setUser(response.data.user);
  };

  const register = async (name, email, password) => {
    await axios.post(`${BASE_URL}/register`, { name, email, password });
  };

  const logout = async () => {
    await axios.post(`${BASE_URL}/logout`, null, {
      headers: { Authorization: `Bearer ${token}` }
    });
    setToken(null);
    setUser(null);
  };

  const authAxios = useMemo(() => {
    const instance = axios.create({
      baseURL: BASE_URL,
      headers: {
        'Content-Type': 'application/json'
      }
    });

    instance.interceptors.request.use((config) => {
      const headers = {
        ...config.headers,
        'Content-Type': 'application/json'
      };

      if (token) {
        headers.Authorization = `Bearer ${token}`;
      }

      return {
        ...config,
        headers
      };
    });

    return instance;
  }, [token]);

  return (
    <AuthContext.Provider value={{ token, user, login, register, logout, authAxios, BASE_URL }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
