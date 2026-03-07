import requests

# Login
login_url = 'http://192.168.126.131:5000/login'
score_url = 'http://192.168.126.131:5000/api/user/score'

session = requests.Session()

# Se connecter
login_data = {'username': 'student', 'password': 'student123'}
r = session.post(login_url, json=login_data)
print('Login response:', r.json())

# Récupérer le score
r = session.get(score_url)
print('Score response:', r.json())
