import React, { Component } from 'react';
import List from './List';
import { DropdownButton, Dropdown } from 'react-bootstrap';

class FilteredList extends Component {
  constructor(props) {
    super(props);
    this.state = {
      search: '',
      type: 'All'
    };
  }

  onSearch = (event) => {
    this.setState({ search: event.target.value.toLowerCase() });
  }

  onFilter = (event) => {
    this.setState({ type: event });
  }

  filterItem = (item) => {
    const matchesSearch = item.name.toLowerCase().indexOf(this.state.search) !== -1;
    const matchesType = this.state.type === 'All' || item.type === this.state.type;
    return matchesSearch && matchesType;
  }

  render() {
    return (
      <div>
        <h1>Produce List</h1>
        <input 
          type="text" 
          placeholder="Search" 
          onChange={this.onSearch}
        />
        <DropdownButton 
          title={this.state.type} 
          id="dropdown-basic"
          onSelect={this.onFilter}
        >
          <Dropdown.Item eventKey="All">All</Dropdown.Item>
          <Dropdown.Item eventKey="Fruit">Fruit</Dropdown.Item>
          <Dropdown.Item eventKey="Vegetable">Vegetable</Dropdown.Item>
        </DropdownButton>
        <List items={this.props.items.filter(this.filterItem)} />
      </div>
    );
  }
}

export default FilteredList;
