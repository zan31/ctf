FROM reg.dctf.si/python:3.11-slim

WORKDIR /app

COPY requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r /app/requirements.txt

COPY app.py /app/app.py
COPY static /app/static
RUN mkdir uploads


EXPOSE 8888

CMD ["python3", "/app/app.py"]
