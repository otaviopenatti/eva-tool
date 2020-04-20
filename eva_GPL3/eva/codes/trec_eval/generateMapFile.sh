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

#!/bin/bash
collection_dir=$1
extension=$2
base=$3

collection_dirname=`dirname $collection_dir`
collection_basename=`basename $collection_dir`
map_file=$base.map

if [ -e $map_file ]; then
  rm $map_file
  echo "map file ($map_file) deleted!"
else
  touch $map_file
fi

cd $collection_dir
files=`find . | grep $extension | sort`

# volta para o diretório de execução e redireciona saída para /dev/null
cd - >> /dev/null 

i=0
for file in $files;
do
  newLength=`expr length $file`
  newLength=`expr $newLength - 1`
  newFile=`expr substr $file 3 $newLength`
  echo -e "$i=$newFile" >> $map_file
  let i++
done
