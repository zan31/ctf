#!/usr/bin/env python3

import base64
import json
import os
import time
import uuid
from pathlib import Path

from flask import Flask, jsonify, make_response, redirect, request, send_from_directory
from pymongo import MongoClient
from werkzeug.utils import secure_filename

BASE_DIR = Path(__file__).resolve().parent
STATIC_DIR = BASE_DIR / 'static'
UPLOAD_DIR = BASE_DIR / 'uploads'

DB_HOST = os.getenv('DB_HOST', 'db')
DB_PORT = int(os.getenv('DB_PORT', '4444'))
DB_NAME = os.getenv('DB_NAME', 'house_market')
DB_USER = os.getenv('DB_USER', '')
DB_PASS = os.getenv('DB_PASS', '')

MAX_IMAGE_SIZE = 2 * 1024 * 1024
PNG_MAGIC = b'\x89PNG\r\n\x1a\n'

app = Flask(__name__)


def get_db():
    if DB_USER and DB_PASS:
        uri = 'mongodb://{}:{}@{}:{}/{}'.format(DB_USER, DB_PASS, DB_HOST, DB_PORT, DB_NAME)
    else:
        uri = 'mongodb://{}:{}/{}'.format(DB_HOST, DB_PORT, DB_NAME)
    client = MongoClient(uri)
    return client[DB_NAME]


def users_collection():
    return get_db()['users']


def houses_collection():
    return get_db()['houses']


def encode_session_token(session_dict):
    raw = json.dumps(session_dict, separators=(',', ':')).encode('utf-8')
    return base64.urlsafe_b64encode(raw).decode('utf-8')


def decode_session_token(token):
    try:
        raw = base64.urlsafe_b64decode(token.encode('utf-8'))
        data = json.loads(raw.decode('utf-8'))
        if not isinstance(data, dict):
            return None
        return data
    except Exception:
        return None


def get_current_session():
    token = request.cookies.get('session_token', '')
    if not token:
        return None
    session_data = decode_session_token(token)
    if not session_data:
        return None
    if 'uid' not in session_data or 'username' not in session_data:
        return None
    return session_data


def require_session():
    session_data = get_current_session()
    if not session_data:
        return None, (jsonify({'ok': False, 'error': 'Unauthorized'}), 401)
    return session_data, None


def is_valid_png(uploaded_file):
    uploaded_file.stream.seek(0, os.SEEK_END)
    size = uploaded_file.stream.tell()
    uploaded_file.stream.seek(0)
    if size <= 0 or size > MAX_IMAGE_SIZE:
        return False

    header = uploaded_file.stream.read(len(PNG_MAGIC))
    uploaded_file.stream.seek(0)

    return header == PNG_MAGIC


def parse_maybe_json(value):
    if not isinstance(value, str):
        return value
    candidate = value.strip()
    if not candidate.startswith('{'):
        return value
    try:
        parsed = json.loads(candidate)
        if isinstance(parsed, dict):
            return parsed
    except Exception:
        return value
    return value


@app.get('/')
def root():
    return redirect('/login')


@app.get('/login')
def login_page():
    return send_from_directory(STATIC_DIR, 'login.html')


@app.get('/register')
def register_page():
    return send_from_directory(STATIC_DIR, 'register.html')


@app.get('/market')
def market_page():
    return send_from_directory(STATIC_DIR, 'market.html')


@app.get('/profile')
def profile_page():
    return send_from_directory(STATIC_DIR, 'profile.html')


@app.get('/style.css')
def style_css():
    return send_from_directory(STATIC_DIR, 'style.css')


@app.get('/app.js')
def app_js():
    return send_from_directory(STATIC_DIR, 'app.js')


@app.post('/api/register')
def register():
    body = request.form if request.form else request.json or {}

    username = str(body.get('username', '')).strip()
    password = str(body.get('password', '')).strip()

    if not username or not password:
        return jsonify({'ok': False, 'error': 'username and password are required'}), 400

    user_id = uuid.uuid4().hex
    users = users_collection()

    if users.find_one({'username': username}):
        return jsonify({'ok': False, 'error': 'registration failed', 'detail': 'username exists'}), 400

    users.insert_one({
        '_id': user_id,
        'id': user_id,
        'username': username,
        'password': password,
        'user_notes': '',
        'created_at': time.time(),
    })
    return jsonify({'ok': True, 'id': user_id})


@app.post('/api/login')
def login():
    body = request.form if request.form else request.json or {}

    username = str(body.get('username', ''))
    password = str(body.get('password', ''))

    query = {
        'username': parse_maybe_json(username),
        'password': parse_maybe_json(password),
    }

    row = users_collection().find_one(query)
    if not row:
        return jsonify({'ok': False, 'error': 'Invalid credentials'}), 401

    session_data = {
        'uid': row['id'],
        'username': row['username'],
        'token_id': uuid.uuid4().hex,
    }
    token = encode_session_token(session_data)

    response = make_response(jsonify({'ok': True, 'message': 'Logged in'}))
    response.set_cookie('session_token', token, httponly=True, samesite='Lax')
    return response


@app.post('/api/logout')
def logout():
    response = make_response(jsonify({'ok': True}))
    response.set_cookie('session_token', '', expires=0)
    return response


@app.get('/api/me')
def me():
    session_data = get_current_session()
    if not session_data:
        return jsonify({'ok': False, 'error': 'Unauthorized'}), 401
    return jsonify({'ok': True, 'session': session_data})


@app.get('/api/profile')
def get_profile():
    session_data, unauthorized = require_session()
    if unauthorized:
        return unauthorized

    row = users_collection().find_one({'id': session_data['uid']})
    if not row:
        return jsonify({'ok': False, 'error': 'profile not found'}), 404

    return jsonify({
        'ok': True,
        'profile': {
            'id': row['id'],
            'username': row['username'],
            'user_notes': row.get('user_notes', ''),
        },
    })


@app.post('/api/profile')
def update_profile():
    session_data, unauthorized = require_session()
    if unauthorized:
        return unauthorized

    body = request.form if request.form else request.json or {}
    user_notes = str(body.get('user_notes', ''))

    users_collection().update_one({'id': session_data['uid']}, {'$set': {'user_notes': user_notes}})
    return jsonify({'ok': True})


@app.get('/api/houses/search')
def search_houses():
    q = str(request.args.get('q', ''))

    filt = parse_maybe_json(q)
    if isinstance(filt, str):
        filt = {
            '$or': [
                {'title': {'$regex': filt, '$options': 'i'}},
                {'city': {'$regex': filt, '$options': 'i'}},
            ]
        }

    rows = list(houses_collection().find(filt).sort('created_at', -1).limit(50))
    houses = []
    for row in rows:
        houses.append({
            'id': row['id'],
            'owner_id': row['owner_id'],
            'title': row['title'],
            'city': row['city'],
            'price': float(row['price']),
            'description': row['description'],
            'visibility': row['visibility'],
            'image_path': row['image_path'],
            'image_header': row['image_path'],
        })

    return jsonify({'ok': True, 'houses': houses})


@app.post('/api/houses/upload')
def upload_house():
    session_data, unauthorized = require_session()
    if unauthorized:
        return unauthorized

    title = str(request.form.get('title', '')).strip()
    city = str(request.form.get('city', '')).strip()
    description = str(request.form.get('description', '')).strip()
    price_raw = str(request.form.get('price', '')).strip()
    visibility = str(request.form.get('visibility', 'public')).strip().lower()
    image = request.files.get('image')

    if not title or not city or not description or not price_raw or image is None:
        return jsonify({'ok': False, 'error': 'all fields are required'}), 400

    try:
        price = float(price_raw)
    except ValueError:
        return jsonify({'ok': False, 'error': 'invalid price'}), 400

    if visibility not in ('public', 'private'):
        return jsonify({'ok': False, 'error': 'visibility must be public or private'}), 400

    filename = secure_filename(image.filename or '')
    if not filename.lower().endswith('.png'):
        return jsonify({'ok': False, 'error': 'only .png files are allowed'}), 400

    if not is_valid_png(image):
        return jsonify({'ok': False, 'error': 'invalid png file'}), 400

    post_id = uuid.uuid4().hex
    stored_name = '{}.png'.format(post_id)
    dst = UPLOAD_DIR / stored_name
    image.save(dst)

    image_path = '/uploads/{}'.format(stored_name)
    houses_collection().insert_one({
        '_id': post_id,
        'id': post_id,
        'owner_id': session_data['uid'],
        'title': title,
        'city': city,
        'price': price,
        'description': description,
        'image_path': image_path,
        'visibility': visibility,
        'created_at': time.time(),
    })

    return jsonify({'ok': True, 'id': post_id})


@app.get('/uploads/<path:name>')
def uploaded_file(name):
    return send_from_directory(UPLOAD_DIR, name)


@app.get('/api/houses/image')
def image_by_header():
    selected = request.headers.get('X-Image-Path', '')
    if not selected:
        return jsonify({'ok': False, 'error': 'X-Image-Path header is required'}), 400

    unsafe_path = (BASE_DIR / selected.lstrip('/')).resolve()
    try:
        blob = unsafe_path.read_bytes()
    except Exception as exc:
        return jsonify({'ok': False, 'error': 'failed to read image', 'detail': str(exc)}), 404

    response = make_response(blob)
    response.headers['Content-Type'] = 'application/octet-stream'
    return response


@app.get('/api/houses/my')
def my_houses():
    session_data, unauthorized = require_session()
    if unauthorized:
        return unauthorized

    rows = list(houses_collection().find({'owner_id': session_data['uid']}).sort('created_at', -1))
    houses = []
    for row in rows:
        houses.append({
            'id': row['id'],
            'owner_id': row['owner_id'],
            'title': row['title'],
            'city': row['city'],
            'price': float(row['price']),
            'description': row['description'],
            'image_path': row['image_path'],
            'visibility': row['visibility'],
        })

    return jsonify({'ok': True, 'houses': houses})


if __name__ == '__main__':
    UPLOAD_DIR.mkdir(parents=True, exist_ok=True)
    app.run(host='0.0.0.0', port=8888, debug=False)
