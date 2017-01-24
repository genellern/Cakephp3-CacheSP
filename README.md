# Cakephp3-CacheSP
Cache parameters to call Mysql stored procedures.


The behavior caches parameters by name and makes calls to Mysql stored procedures in a clean and more readable way.

## Background

Many calls to stored procedures are dirty and confusing, so I tried to put this in a better, hope you enjoy it and contribute if you think something could be better, I know I have to write some testing.


## Requirements

* CakePHP 3.x
* Patience

## Usage

- Load it in your table.

    `$this->addBehavior('CacheSP');`
- Call your procedure:

	`$this->callSP('party_roles_relate', ['partyId'=>$partyId, 'roleName'=>User::$rolCode]);`

    Call it with expressions:

    Create the query object.
    `$query = $this->find();`

    Create the expression and pass it.
    `$this->callSP('party_roles_last_request', ['partyId'=>$partyId, 'updateTime'=>$query->func()->now(), 'roleName'=>User::$rolCode]);`

- This will generate a query like this:

	`CALL party_roles_relate('USER', '1')`

    Called  with expressions:

    `CALL party_roles_relate_record ('Generator', NOW())`

- A description of my procedure.

	`CREATE PROCEDURE party_roles_relate (IN roleName varchar (45), IN partyId int(11))`

	NOTE: As you can see, order doesn't matter anymore :)
- Enjoy.


## License

The MIT License (MIT)

Copyright (c) 2015 Geneller Naranjo

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

