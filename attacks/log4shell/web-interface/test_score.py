import os
import requests

base_url = os.environ.get('BASE_URL', 'http://127.0.0.1:5000').rstrip('/')
login_url = f'{base_url}/login'
score_url = f'{base_url}/api/user/score'

session = requests.Session()

# Se connecter
login_data = {'username': 'student', 'password': 'student123'}
r = session.post(login_url, json=login_data)
print('Login response:', r.json())

# Récupérer le score
r = session.get(score_url)
print('Score response:', r.json())
