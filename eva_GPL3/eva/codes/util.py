#
#    This file is part of Eva tool.
#
#    Eva is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    Eva is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with Eva. If not, see <http://www.gnu.org/licenses/>.
#
#    For commercial use of Eva, please contact me.
#
#    COPYRIGHT 2010-2013  - Otavio A. B. Penatti - otavio_at_penatti_dot_com
#

import psycopg2

#Funcao usada para conectar no banco de dados
def connect():
    #usando postgres
    conn = psycopg2.connect("dbname='eva' user='otapena' host='localhost' password='otavio'")

    #altera o encoding do banco
    cur = conn.cursor()
    cur.execute("SET CLIENT_ENCODING TO 'LATIN1';")
    conn.commit()
    cur.close()

    return conn
